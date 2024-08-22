<?php

class UserManager {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    function generate_username($name, $surname) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        $base_username = substr($name, 0, 3) . substr($surname, 0, 3);
        $username = $base_username;
        $counter = 1;
    
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_username = %s", $username)) > 0) {
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

    public function insert_user_with_generated_credentials($name, $surname, $role, $password, $api_key='', $tied_to_teacher='') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
    
        $username = $this->generate_username($name, $surname);
        if ($password == '') {
            $password = $this->generate_random_password();
        }
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $temporary_password = '';

        if ($role == 'student') {
            $temporary_password = $password;
        }
        
        $wpdb->insert(
            $table_name,
            array(
                'user_name' => $name,
                'user_surname' => $surname,
                'user_username' => $username,
                'user_password' => $hashed_password,
                'user_role' => $role,
                'tied_to_teacher' => $tied_to_teacher,
                'temporary_password' => $temporary_password,
                'api_key' => $api_key
            )
        );
        
        //if role is student then return with password, otherwise return without password
        if($role == 'student'){
            return array('username' => $username, 'password' => $password, 'user_role' => $role); // Return the credentials for display
        }
        else {
            return array('username' => $username, 'user_role' => $role); // Return the credentials for display
        }
    }

    public function get_user_by_username($username) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_username = %s", $username));
    }

    public function update_password($username, $new_password) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        $wpdb->update(
            $table_name,
            array('user_password' => $hashed_password, 'temporary_password' => ''),
            array('user_username' => $username)
        );
    }
}

?>