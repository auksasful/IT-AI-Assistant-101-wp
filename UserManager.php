<?php

require_once 'DataEncryption.php';

class UserManager {
    private $db;
    private $data_encryption;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->data_encryption = new ITAIAssistant_Data_Encryption();
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

        if ($tied_to_teacher != '') {
            $temporary_password = $this->data_encryption->encrypt($password);
        }

        if ($api_key != '') {
            $api_key = $this->data_encryption->encrypt($api_key);
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

    //delete user by username
    public function delete_user($username) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        $wpdb->delete($table_name, array('user_username' => $username));
    }

    public function track_login_attempt($username) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_login_attempts';
        $wpdb->insert(
            $table_name,
            array(
                'user_username' => $username,
                'attempt_time' => current_time('mysql')
            )
        );

        $attempts_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_username = %s AND attempt_time > DATE_SUB(NOW(), INTERVAL 10 MINUTE)", $username));
        return $attempts_count;
    }


    public function get_user_by_username($username) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_username = %s", $username));
    }

    public function get_user_by_api_key($api_key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        
        // Fetch all users from the database
        $users = $wpdb->get_results("SELECT * FROM $table_name");
        
        foreach ($users as $user) {
            // Decrypt the stored API key
            $decrypted_api_key = $this->data_encryption->decrypt($user->api_key);
            
            // Compare the decrypted API key with the inputted API key
            if ($decrypted_api_key === $api_key) {
                return $user;
            }
        }
        
        return null;
    }

    public function get_class_API_key($class_id) {
        $table_name = $this->db->prefix . 'it_ai_assistant101_class';
        //get class_main_teacher based on class_id
        $class_main_teacher = $this->db->get_var($this->db->prepare("SELECT class_main_teacher FROM $table_name WHERE class_id = %d", $class_id));
        $table_name = $this->db->prefix . 'it_ai_assistant101_user';
        $user = $this->db->get_row($this->db->prepare("SELECT * FROM $table_name WHERE user_username = %s", $class_main_teacher));
        return $this->decrypt($user->api_key);
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

    // create temporary password for student
    public function create_temporary_password($username) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        $password = $this->generate_random_password();
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $temporary_password = $this->data_encryption->encrypt($password);
        // error log all passwords:
        $wpdb->update(
            $table_name,
            array('user_password' => $hashed_password, 'temporary_password' => $temporary_password),
            array('user_username' => $username)
        );
        
        return $password;
    }

    function get_decrypted_temporary_password($username) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_username = %s", $username));
        return $this->data_encryption->decrypt($user->temporary_password);
    }

    public function update_user_api_key($username, $api_key) {
        if(!$this->is_api_key_new($api_key)){
            return false;
        }
        $api_connector = new ApiConnector($api_key);
        $jwt_token = $api_connector->test_connection($username, 'teacher', true);
        if ($jwt_token) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
            $encrypted_api_key = $this->data_encryption->encrypt($api_key);
            
            $wpdb->update(
                $table_name,
                array('api_key' => $encrypted_api_key),
                array('user_username' => $username)
            );
            return true;
        } else {
            return false;
        }
    }

    //check if someone uses the api key already
    public function is_api_key_new($api_key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        $users = $wpdb->get_results("SELECT * FROM $table_name");
        foreach ($users as $user) {
            $decrypted_api_key = $this->data_encryption->decrypt($user->api_key);
            if ($decrypted_api_key === $api_key) {
                return false;
            }
        }
        return true;
    }

    // function to check which teacher is tied to the student
    public function get_tied_teacher($username) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_username = %s", $username));
        return $user->tied_to_teacher;
    }

    public function update_user_tied_request($username, $teacher_username, $assign) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';

        if($assign){
            $wpdb->update(
                $table_name,
                array('tied_request' => $teacher_username),
                array('user_username' => $username)
            );
        }
        else{
            $wpdb->update(
                $table_name,
                array('tied_request' => '', 'tied_to_teacher' => $teacher_username),
                array('user_username' => $username)
            );

            $teacher_user = $this->get_user_by_username($teacher_username);
            var_dump($teacher_user);
            $table_name = $this->db->prefix . 'it_ai_assistant101_class_user';
            // check if not exists
            $class_user_count = $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM $table_name WHERE class_id = %d AND user_username = %s", $teacher_user->default_class_id, $username));
            if($class_user_count > 0) {
                return;
            }

            $this->db->insert(
                $table_name,
                array(
                    'class_id' => $teacher_user->default_class_id,
                    'user_username' => $username
                )
            );
        }
    }

    //Get tied requests count to current teacher
    public function get_tied_requests_count($teacher_username) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE tied_request = %s", $teacher_username));
    }

    public function decrypt($encrypted_text) {
        return $this->data_encryption->decrypt($encrypted_text);
    }
    
}

?>