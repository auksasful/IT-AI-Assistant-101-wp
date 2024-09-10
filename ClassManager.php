<?php

class ClassManager {
    private $db;


    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function insert_class($class_name, $class_main_teacher) {
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
        $this->insert_class_user($class_id, $class_main_teacher);
    }

    public function insert_class_user($class_id, $user_username) {
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
        return $results;
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

}
?>