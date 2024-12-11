<?php

class TaskManager {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function insert_task($task_name, $task_text, $task_type, $class_id, $task_file_clean = null, $task_file_correct = null, $task_file_uri = null, $clean_task_file_uri = null, $system_prompt = null, $default_summary = null, $default_self_check_questions = null) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $this->db->insert(
            $table_name,
            array(
                'task_name' => $task_name,
                'task_text' => $task_text,
                'task_type' => $task_type,
                'task_file_clean' => $task_file_clean,
                'task_file_correct' => $task_file_correct,
                'task_file_uri' => $task_file_uri,
                'clean_task_file_uri' => $clean_task_file_uri,
                'system_prompt' => $system_prompt,
                'default_summary' => $default_summary,
                'default_self_check_questions' => $default_self_check_questions,
                'class_id' => $class_id
            )
        );
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

    public function get_task($task_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $sql = "SELECT * FROM $table_name WHERE task_id = %d";
        $result = $this->db->get_row($this->db->prepare($sql, $task_id));
        return $result;
    }

    public function update_task($task_id, $task_name, $task_text, $task_type, $task_file_clean = null, $task_file_correct = null, $task_file_uri = null, $clean_task_file_uri = null, $system_prompt = null, $default_summary = null, $default_self_check_questions = null) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $this->db->update(
            $table_name,
            array(
                'task_name' => $task_name,
                'task_text' => $task_text,
                'task_type' => $task_type,
                'task_file_clean' => $task_file_clean,
                'task_file_correct' => $task_file_correct,
                'task_file_uri' => $task_file_uri,
                '$clean_task_file_uri' => $clean_task_file_uri,
                'system_prompt' => $system_prompt,
                'default_summary' => $default_summary,
                'default_self_check_questions' => $default_self_check_questions
            ),
            array('task_id' => $task_id)
        );
    }

    public function delete_task($task_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $this->db->delete(
            $table_name,
            array('task_id' => $task_id)
        );
    }

    public function insert_student_task_solution($task_id, $class_id, $user_username, $solution_file = null, $solution_file_uri = null) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_student_task_solution';
        
        // Check if a solution already exists for the given task_id, class_id, and user_username
        $sql = "SELECT id FROM $table_name WHERE task_id = %d AND class_id = %d AND user_username = %s";
        $existing_solution = $this->db->get_var($this->db->prepare($sql, $task_id, $class_id, $user_username));
        
        if ($existing_solution) {
            // Update the existing solution
            $this->update_student_task_solution($existing_solution, $task_id, $class_id, $user_username, $solution_file, $solution_file_uri);
        } else {
            // Insert a new solution
            $this->db->insert(
                $table_name,
                array(
                    'task_id' => $task_id,
                    'class_id' => $class_id,
                    'user_username' => $user_username,
                    'solution_file' => $solution_file,
                    'solution_file_uri' => $solution_file_uri
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
    
    public function update_student_task_solution($id, $task_id, $class_id, $user_username, $solution_file = null, $solution_file_uri = null) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_student_task_solution';
        $this->db->update(
            $table_name,
            array(
                'task_id' => $task_id,
                'class_id' => $class_id,
                'user_username' => $user_username,
                'solution_file' => $solution_file,
                'solution_file_uri' => $solution_file_uri
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


}