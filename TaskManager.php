<?php

class TaskManager {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function insert_task($task_name, $task_text, $task_type, $class_id, $task_file_clean = null, $task_file_correct = null, $task_file_uri = null, $system_prompt = null, $default_summary = null, $default_self_check_questions = null) {
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
                'system_prompt' => $system_prompt,
                'default_summary' => $default_summary,
                'default_self_check_questions' => $default_self_check_questions,
                'class_id' => $class_id
            )
        );
    }

    public function get_tasks_by_class_id($class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $sql = "SELECT * FROM $table_name WHERE class_id = %d";
        $results = $this->db->get_results($this->db->prepare($sql, $class_id));
        return $results;
    }

    public function get_task($task_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_task';
        $sql = "SELECT * FROM $table_name WHERE task_id = %d";
        $result = $this->db->get_row($this->db->prepare($sql, $task_id));
        return $result;
    }

    public function update_task($task_id, $task_name, $task_text, $task_type, $task_file_clean = null, $task_file_correct = null, $task_file_uri = null, $system_prompt = null, $default_summary = null, $default_self_check_questions = null) {
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
}