<?php

class TaskManager {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function insert_task($task_name, $task_text, $task_type, $class_id, $task_file_clean = null, $task_file_correct = null, $python_data_file = null, $orange_data_file=null, $task_file_uri = null, $clean_task_file_uri = null, $python_data_file_uri = null, $orange_data_file_uri = null, $python_program_execution_result = null, $orange_program_execution_result = null, $system_prompt = null, $default_summary = null, $default_self_check_questions = null) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $this->db->insert(
            $table_name,
            array(
                'task_name' => $task_name,
                'task_text' => $task_text,
                'task_type' => $task_type,
                'task_file_clean' => $task_file_clean,
                'task_file_correct' => $task_file_correct,
                'python_data_file' => $python_data_file,
                'orange_data_file' => $orange_data_file,
                'task_file_uri' => $task_file_uri,
                'clean_task_file_uri' => $clean_task_file_uri,
                'python_data_file_uri' => $python_data_file_uri,
                'orange_data_file_uri' => $orange_data_file_uri,
                'python_program_execution_result' => $python_program_execution_result,
                'orange_program_execution_result' => $orange_program_execution_result,
                'system_prompt' => $system_prompt,
                'default_summary' => $default_summary,
                'default_self_check_questions' => $default_self_check_questions,
                'class_id' => $class_id
            )
        );
        return $this->db->insert_id;
    }

    public function get_tasks_by_class_id($class_id, $user_username) {
        $user_table_name = $this->db->prefix . 'it_ai_assistant101_class_user';
        $task_table_name = $this->db->prefix . 'it_ai_assistant101_task';

        // Check if the user is in the class
        $user_check_sql = "SELECT COUNT(*) FROM $user_table_name WHERE class_id = %d AND user_username = %s";
        $user_exists = $this->db->get_var($this->db->prepare($user_check_sql, $class_id, $user_username));

        if ($user_exists) {
            $sql = "SELECT * FROM $task_table_name WHERE class_id = %d";
            $results = $this->db->get_results($this->db->prepare($sql, $class_id));
            return $results;
        } else {
            return array(); // Return an empty array if the user is not in the class
        }
    }

    public function get_user_class_task_files_for_export($class_id) {
        $task_table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $sql = "SELECT * FROM $task_table_name WHERE class_id = %d";
        // return json list of task files with this structure:
        // task_name, task_text, task_type, task_file_clean, task_file_correct, python_data_file, orange_data_file, python_program_execution_result, orange_program_execution_result, system_prompt, default_summary, default_self_check_questions
        $results = $this->db->get_results($this->db->prepare($sql, $class_id));
        // structure the results into json
        $json_results = array();
        $files = array();
        foreach ($results as $result) {
            $json_results[] = array(
                'task_name' => $result->task_name,
                'task_text' => $result->task_text,
                'task_type' => $result->task_type,
                'task_file_clean' => $result->task_file_clean,
                'task_file_correct' => $result->task_file_correct,
                'python_data_file' => $result->python_data_file,
                'orange_data_file' => $result->orange_data_file,
                'python_program_execution_result' => $result->python_program_execution_result,
                'orange_program_execution_result' => $result->orange_program_execution_result,
                'system_prompt' => $result->system_prompt,
                'default_summary' => $result->default_summary,
                'default_self_check_questions' => $result->default_self_check_questions
            );
            // collect all files: task_file_clean, task_file_correct, python_data_file, orange_data_file into a separate array
            // if they are not empty or null
            if ($result->task_file_clean) {
                $files[] = $result->task_file_clean;
            }
            if ($result->task_file_correct) {
                $files[] = $result->task_file_correct;
            }
            if ($result->python_data_file) {
                $files[] = $result->python_data_file;
            }
            if ($result->orange_data_file) {
                $files[] = $result->orange_data_file;
            }
        }
        return array('task_files' => $json_results, 'files' => $files);
    }

    public function get_task($task_id, $user_username) {
        $task_table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $user_table_name = $this->db->prefix . 'it_ai_assistant101_class_user';

        // Check if the user is associated with the task
        $user_check_sql = "SELECT COUNT(*) FROM $user_table_name WHERE class_id = (SELECT class_id FROM $task_table_name WHERE task_id = %d) AND user_username = %s";
        $user_exists = $this->db->get_var($this->db->prepare($user_check_sql, $task_id, $user_username));

        if ($user_exists) {
            $sql = "SELECT * FROM $task_table_name WHERE task_id = %d";
            $result = $this->db->get_row($this->db->prepare($sql, $task_id));
            return $result;
        } else {
            return null; // Return null if the user is not associated with the task
        }
    }

    public function update_task(
        $task_id,
        $task_name,
        $task_text,
        $task_type,
        $class_id,
        $task_file_clean = null,
        $task_file_correct = null,
        $python_data_file = null,
        $orange_data_file = null,
        $task_file_uri = null,
        $clean_task_file_uri = null,
        $python_data_file_uri = null,
        $orange_data_file_uri = null,
        $python_program_execution_result = null,
        $orange_program_execution_result = null,
        $system_prompt = null,
        $default_summary = null,
        $default_self_check_questions = null
    ) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $this->db->update(
            $table_name,
            array(
                'task_name' => $task_name,
                'task_text' => $task_text,
                'task_type' => $task_type,
                'class_id' => $class_id,
                'task_file_clean' => $task_file_clean,
                'task_file_correct' => $task_file_correct,
                'python_data_file' => $python_data_file,
                'orange_data_file' => $orange_data_file,
                'task_file_uri' => $task_file_uri,
                'clean_task_file_uri' => $clean_task_file_uri,
                'python_data_file_uri' => $python_data_file_uri,
                'orange_data_file_uri' => $orange_data_file_uri,
                'python_program_execution_result' => $python_program_execution_result,
                'orange_program_execution_result' => $orange_program_execution_result,
                'system_prompt' => $system_prompt,
                'default_summary' => $default_summary,
                'default_self_check_questions' => $default_self_check_questions
            ),
            array('task_id' => $task_id)
        );
        error_log("Task updated in the database: " . $task_id);
    }

    public function delete_task($task_id, $user_username, $class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $class_table_name = $this->db->prefix . 'it_ai_assistant101_class';
        #check if the user is class_main_teacher
        $sql = "SELECT class_main_teacher FROM $class_table_name WHERE class_id = %d";
        $class_main_teacher = $this->db->get_var($this->db->prepare($sql, $class_id));
        if ($class_main_teacher == $user_username) {
            $this->db->delete(
                $table_name,
                array('task_id' => $task_id)
            );
            return true;
        }
        return false;
        
    }

    public function insert_student_task_solution($task_id, $class_id, $user_username, $solution_file = null, $solution_file_uri = null, $solution_file_mime_type = null) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_student_task_solution';
        
        // Check if a solution already exists for the given task_id, class_id, and user_username
        $sql = "SELECT id FROM $table_name WHERE task_id = %d AND class_id = %d AND user_username = %s";
        $existing_solution = $this->db->get_var($this->db->prepare($sql, $task_id, $class_id, $user_username));
        
        if ($existing_solution) {
            // Update the existing solution
            $this->update_student_task_solution($existing_solution, $task_id, $class_id, $user_username, $solution_file, $solution_file_uri, $solution_file_mime_type);
        } else {
            // Insert a new solution
            $this->db->insert(
                $table_name,
                array(
                    'task_id' => $task_id,
                    'class_id' => $class_id,
                    'user_username' => $user_username,
                    'solution_file' => $solution_file,
                    'solution_file_uri' => $solution_file_uri,
                    'solution_file_mime_type' => $solution_file_mime_type
                )
            );
        }
    }

    public function get_first_student_task_solution($task_id, $class_id, $user_username) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_student_task_solution';
        $sql = "SELECT * FROM $table_name WHERE task_id = %d AND class_id = %d AND user_username = %s LIMIT 1";
        $result = $this->db->get_row($this->db->prepare($sql, $task_id, $class_id, $user_username));
        return $result;
    }
    
    public function update_student_task_solution($id, $task_id, $class_id, $user_username, $solution_file = null, $solution_file_uri = null, $solution_file_mime_type = null) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_student_task_solution';
        $this->db->update(
            $table_name,
            array(
                'task_id' => $task_id,
                'class_id' => $class_id,
                'user_username' => $user_username,
                'solution_file' => $solution_file,
                'solution_file_uri' => $solution_file_uri,
                'solution_file_mime_type' => $solution_file_mime_type
            ),
            array('id' => $id)
        );
    }

    public function delete_student_task_solution($id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_student_task_solution';
        $this->db->delete(
            $table_name,
            array('id' => $id)
        );
    }

    public function insert_student_task_chat_history($task_id, $class_id, $user_username, $message_role, $system_message, $user_message) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_student_task_chat_history';
        $this->db->insert(
            $table_name,
            array(
                'task_id' => $task_id,
                'class_id' => $class_id,
                'user_username' => $user_username,
                'message_role' => $message_role,
                'system_message' => $system_message,
                'user_message' => $user_message
            )
        );
        return $this->db->insert_id;
    }

    public function get_student_task_chat_history($task_id, $class_id, $user_username) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_student_task_chat_history';
        $sql = "SELECT * FROM $table_name WHERE task_id = %d AND class_id = %d AND user_username = %s";
        $results = $this->db->get_results($this->db->prepare($sql, $task_id, $class_id, $user_username));
        return $results;
    }

    public function update_student_task_chat_history($id, $message_role, $system_message, $user_message) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_student_task_chat_history';
        $this->db->update(
            $table_name,
            array(
                'message_role' => $message_role,
                'system_message' => $system_message,
                'user_message' => $user_message
            ),
            array('id' => $id)
        );
    }

    public function delete_student_task_chat_history($id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_student_task_chat_history';
        $this->db->delete(
            $table_name,
            array('id' => $id)
        );
    }


}