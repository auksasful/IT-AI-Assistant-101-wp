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
            array('default_class_id' => $class_id, 'last_used_class_id' => $class_id, 'tied_to_teacher' => $teacher_username, 'tied_request' => ''),
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

    public function get_class_users($class_id) {
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
        // update the default_class_id and last_used_class_id of the user
        $this->db->update(
            $user_table,
            array('default_class_id' => 0, 'last_used_class_id' => 0, 'tied_to_teacher' => '', 'tied_request' => ''),
            array('user_username' => $user_username)
        );
    }

    public function remove_class($class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_class_user';
        $class_user_count = $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM $table_name WHERE class_id = %d", $class_id));
        if($class_user_count == 1) {
            $this->db->delete(
                $this->db->prefix . 'it_ai_assistant101_class',
                array('class_id' => $class_id)
            );
        }
    }

    public function edit_class($class_id, $class_name) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_class';
        $this->db->update(
            $table_name,
            array('class_name' => $class_name),
            array('class_id' => $class_id)
        );
    }

    public function set_default_class($user_username, $class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_user';
        $this->db->update(
            $table_name,
            array('default_class_id' => $class_id, 'last_used_class_id' => $class_id),
            array('user_username' => $user_username)
        );
    }

    public function set_last_used_class_id($user_username, $class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_user';
        $this->db->update(
            $table_name,
            array('last_used_class_id' => $class_id),
            array('user_username' => $user_username)
        );
    }
}
?>