<?php

class UserManager {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    function generate_username($name, $surname) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tbit_ai_assistant101_student';
        $base_username = substr($name, 0, 3) . substr($surname, 0, 3);
        $username = $base_username;
        $counter = 1;
    
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE student_username = %s", $username)) > 0) {
            $username = $base_username . $counter;
            $counter++;
        }
    
        return $username;
    }

    function generate_random_password($length = 12) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function insert_student_with_generated_credentials($name, $surname) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tbit_ai_assistant101_student';
    
        $username = $this->generate_username($name, $surname);
        $password = $this->generate_random_password();
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
        $wpdb->insert(
            $table_name,
            array(
                'student_name' => $name,
                'student_surname' => $surname,
                'student_username' => $username,
                'student_password' => $hashed_password
            )
        );
    
        return array('username' => $username, 'password' => $password); // Return the credentials for display
    }
}

?>