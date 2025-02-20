<?php

class ClassManager {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function insert_class($class_name, $class_main_teacher, $default_class = false) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_class';
        $class_creation_date = date('Y-m-d H:i:s');
        $this->db->insert(
            $table_name,
            array(
                'class_name' => $class_name,
                'class_main_teacher' => $class_main_teacher,
                'class_creation_date' => $class_creation_date
            )
        );
        //get the last inseted entry and it's field class_id value
        $class_id = $this->db->insert_id;
        $this->insert_class_user($class_id, $class_main_teacher, '');
        if($default_class) {
            $this->set_default_class($class_main_teacher, $class_id);
        }
        if($this->db->last_error) {
            return false;
        }
        return true;
    }

    public function insert_class_user($class_id, $user_username, $teacher_username) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_class_user';
        // check if not exists
        $class_user_count = $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM $table_name WHERE class_id = %d AND user_username = %s", $class_id, $user_username));
        if($class_user_count > 0) {
            return;
        }
        $this->db->insert(
            $table_name,
            array(
                'class_id' => $class_id,
                'user_username' => $user_username
            )
        );

        $user_table = $this->db->prefix . 'it_ai_assistant101_user';
        // update the default_class_id and last_used_class_id of the user
        $this->db->update(
            $user_table,
            array('last_used_class_id' => $class_id, 'tied_to_teacher' => $teacher_username, 'tied_request' => ''),
            array('user_username' => $user_username)
        );
    }

    public function get_classes_by_username($user_username) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_class';
        $class_user_table = $this->db->prefix . 'it_ai_assistant101_class_user';
        $sql = "SELECT * FROM $table_name WHERE class_id IN (SELECT class_id FROM $class_user_table WHERE user_username = %s)";
        $results = $this->db->get_results($this->db->prepare($sql, $user_username));
        return $results;
    }
    
    public function get_class_by_id($class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_class';
        $result = $this->db->get_row($this->db->prepare("SELECT * FROM $table_name WHERE class_id = %d", $class_id));
        return $result;
    }

    public function get_class_users($class_id, $current_user) {
        $class_table_name = $this->db->prefix . 'it_ai_assistant101_class';
        $class_main_teacher = $this->db->get_var($this->db->prepare("SELECT class_main_teacher FROM $class_table_name WHERE class_id = %d", $class_id));
        $table_name = $this->db->prefix . 'it_ai_assistant101_class_user';
        $sql = "SELECT * FROM $table_name WHERE class_id = %d";
        $results = $this->db->get_results($this->db->prepare($sql, $class_id));
        // get users from the users table
        $user_table = $this->db->prefix . 'it_ai_assistant101_user';
        $finalResults = array();
        foreach($results as $key => $result) {
            $user = $this->db->get_row($this->db->prepare("SELECT * FROM $user_table WHERE user_username = %s", $result->user_username));
            // append user to the FINAL result
            $finalResults[] = $user;
        }
        $current_user_from_db = $this->db->get_row($this->db->prepare("SELECT * FROM $user_table WHERE user_username = %s", $current_user));
        if ($current_user_from_db->user_username == $class_main_teacher && $current_user_from_db->default_class_id == $class_id) {
            $user_requests = $this->db->get_results($this->db->prepare("SELECT * FROM $user_table WHERE tied_request = %s", $current_user_from_db->user_username));
            foreach($user_requests as $key => $user_request) {
                $finalResults[] = $user_request;
            }
        } 
        return $finalResults;
    }

    public function remove_class_user($class_id, $user_username) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_class_user';
        $this->db->delete(
            $table_name,
            array(
                'class_id' => $class_id,
                'user_username' => $user_username
            )
        );

        $user_table = $this->db->prefix . 'it_ai_assistant101_user';
        // GET the default_class_id of the user
        $tied_to_teacher = $this->db->get_var($this->db->prepare("SELECT tied_to_teacher FROM $user_table WHERE user_username = %s", $user_username));
        $default_class_id = $this->db->get_var($this->db->prepare("SELECT default_class_id FROM $user_table WHERE user_username = %s", $tied_to_teacher));

        if ($default_class_id == $class_id) {
            // update the default_class_id and last_used_class_id of the user
            $this->db->update(
                $user_table,
                array('default_class_id' => 0, 'last_used_class_id' => 0, 'tied_to_teacher' => '', 'tied_request' => ''),
                array('user_username' => $user_username)
            );
            
            $student_classes = $this->db->get_results($this->db->prepare("SELECT * FROM $table_name WHERE user_username = %s", $user_username));
            $class_table = $this->db->prefix . 'it_ai_assistant101_class';
            // remove user from all the teacher's classes
            foreach($student_classes as $key => $student_class) {
                $class_main_teacher = $this->db->get_var($this->db->prepare("SELECT class_main_teacher FROM $class_table WHERE class_id = %d", $student_class->class_id));
                $teacher_user_from_db = $this->db->get_row($this->db->prepare("SELECT * FROM $user_table WHERE user_username = %s", $class_main_teacher));
                if ($teacher_user_from_db->user_username == $tied_to_teacher) {
                    // delete where username is $user_username and tied_request is $class_main_teacher
                    $this->db->delete(
                        $table_name,
                        [
                            'class_id' => $student_class->class_id,
                            'user_username' => $user_username
                        ]
                    );
                }
            }
        }

        // remove user join requests where the user is tied to the teacher and the current class is default class for the teacher
        $class_table_name = $this->db->prefix . 'it_ai_assistant101_class';
        $class_main_teacher = $this->db->get_var($this->db->prepare("SELECT class_main_teacher FROM $class_table_name WHERE class_id = %d", $class_id));
        $teacher_user_from_db = $this->db->get_row($this->db->prepare("SELECT * FROM $user_table WHERE user_username = %s", $class_main_teacher));
        if ($teacher_user_from_db->default_class_id == $class_id) {
        //    delete where username is $user_username and tied_request is $class_main_teacher
            $this->db->update(
                $user_table,
                ['tied_request' => '', 'last_used_class_id' => 0, 'default_class_id' => 0],
                ['user_username' => $user_username]
            );
        }
    }

    public function remove_class($class_id) {
        // remove all the student_task_solution
        $this->db->delete(
            $this->db->prefix . 'it_ai_assistant101_student_task_solution',
            array('class_id' => $class_id)
        );

        // remove all the task_chat_history
        $this->db->delete(
            $this->db->prefix . 'it_ai_assistant101_task_chat_history',
            array('class_id' => $class_id)
        );

        // remove all the class tasks
        $this->db->delete(
            $this->db->prefix . 'it_ai_assistant101_task',
            array('class_id' => $class_id)
        );

        // remove all users from the class
        $this->db->delete(
            $this->db->prefix . 'it_ai_assistant101_class_user',
            array('class_id' => $class_id)
        );

        $this->db->delete(
            $this->db->prefix . 'it_ai_assistant101_class',
            array('class_id' => $class_id)
        );

    }

    public function edit_class($class_id, $class_name) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_class';
        $this->db->update(
            $table_name,
            array('class_name' => $class_name),
            array('class_id' => $class_id)
        );
    }

    public function check_if_teacher_and_class_is_default($user_username, $class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_user';
        $result = $this->db->get_row($this->db->prepare("SELECT * FROM $table_name WHERE user_username = %s", $user_username));
        $class_table_name = $this->db->prefix . 'it_ai_assistant101_class';
        $class_main_teacher = $this->db->get_var($this->db->prepare("SELECT class_main_teacher FROM $class_table_name WHERE class_id = %d", $class_id));
        if ($result->default_class_id == $class_id && $user_username == $class_main_teacher) {
            return true;
        }
        return false;
    }

    public function get_teacher_unadded_students($username, $class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_user';
        $class_user_table = $this->db->prefix . 'it_ai_assistant101_class_user';
        $default_class_id = $this->db->get_var($this->db->prepare("SELECT default_class_id FROM $table_name WHERE user_username = %s", $username));
        # get all the students that are in the default class of the teacher but not in the given $class_id and exclude the teacher
        $sql = "SELECT * FROM $table_name
                WHERE user_username IN (
                    SELECT user_username 
                    FROM $class_user_table
                    WHERE class_id = %d
                )
                AND user_username NOT IN (
                    SELECT user_username
                    FROM $class_user_table
                    WHERE class_id = %d
                )
                AND user_username != %s";
        $results = $this->db->get_results($this->db->prepare($sql, $default_class_id, $class_id, $username));
        return $results;
    }

    public function set_default_class($user_username, $class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_user';
        $this->db->update(
            $table_name,
            array('default_class_id' => $class_id, 'last_used_class_id' => $class_id),
            array('user_username' => $user_username)
        );
    }

    public function set_last_used_class($user_username, $class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_user';
        $this->db->update(
            $table_name,
            array('last_used_class_id' => $class_id),
            array('user_username' => $user_username)
        );
    }

    public function add_users_to_class($class_id, $users) {
        global $wpdb;
        $table_name = "{$wpdb->prefix}it_ai_assistant101_class_user";
        $result = [];
        foreach ($users as $username) {
            $wpdb->insert(
                $table_name,
                [
                    'class_id' => $class_id,
                    'user_username' => $username
                ],
                ['%d', '%s']
            );

            if ($wpdb->insert_id) {
                $result[] = $username;
            }
        }
        return $result;
    }
}
?>