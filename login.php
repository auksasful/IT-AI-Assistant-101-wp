<?php

session_start();

require 'APIConnector.php';
require 'UserManager.php';

// Debug statement to check the script execution
error_log('login.php script executed');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_manager = new UserManager();
    if (isset($_POST['form_register'])) {
        error_log('Form submitted');
        error_log('User Name: ' . $_POST['user_name']);
        error_log('User Surname: ' . $_POST['user_surname']);
        $api_key = $_POST['api_key'];

        if($api_key){
            $api_connector = new ApiConnector($api_key);
            $name = sanitize_text_field($_POST['user_name']);
            $surname = sanitize_text_field($_POST['user_surname']);
            $password = sanitize_text_field($_POST['password']);
            if(!$user_manager->is_api_key_new($api_key)){
                echo 'API key already in use';
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
            $credentials = $user_manager->insert_user_with_generated_credentials($name, $surname, 'teacher', $password, $api_key, '');
            $jwt_token = $api_connector->test_connection($credentials['username'], $credentials['user_role'], true);
            if(!$jwt_token){
                echo 'Failed to connect to API';
                $user_manager->delete_user($credentials['username']);
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
        }
        else {
            $api_connector = new ApiConnector('');
            $name = sanitize_text_field($_POST['user_name']);
            $surname = sanitize_text_field($_POST['user_surname']);
            $password = sanitize_text_field($_POST['password']);
            if(!$user_manager->is_api_key_new($api_key)){
                echo 'API key already in use';
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
            $credentials = $user_manager->insert_user_with_generated_credentials($name, $surname, 'student', $password, $api_key, '');
            $jwt_token = $api_connector->test_connection($credentials['username'], $credentials['user_role'], false);
        }
        // echo 'Student added successfully! Username: ' . $credentials['username'] . ' Password: ' . $credentials['password'];

        // Call the method to test the connection and handle the result

        if ($jwt_token) {
            // Store the JWT token in a session
            $_SESSION['jwt_token'] = $jwt_token;

            // Redirect to the dashboard page
            error_log('Redirecting to dashboard');
            wp_redirect(home_url('/itaiassistant101/dashboard'));
            exit();
        } else {
            $user_manager->delete_user($credentials['username']);
            // Redirect to the login page
            error_log('Redirecting to login (invalid token)');
            wp_redirect(home_url('/itaiassistant101/login'));
            exit();
        }
    } elseif (isset($_POST['general_login'])) {
        error_log('General login form submitted');
        $api_connector = new ApiConnector('');
        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_username = %s", $username));

        if ($user && password_verify($password, $user->user_password)) {
            $jwt_token = $api_connector->test_connection($user->user_username, $user->user_role, false);

            if ($jwt_token) {
                // Store the JWT token in a session
                $_SESSION['jwt_token'] = $jwt_token;
    
                // Redirect to the dashboard page
                error_log('Redirecting to dashboard');
                wp_redirect(home_url('/itaiassistant101/dashboard'));
                exit();
            } else {
                // Redirect to the login page
                error_log('Redirecting to login (invalid token)');
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
        } else {
            error_log('Invalid username or password');
            echo 'Invalid username or password';
                
            $login_attempts = $user_manager->track_login_attempt($username);
            if($login_attempts > 10){
                echo 'Too many login attempts, wait 10 minutes';
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
        }
    }
}

// Check if the JWT token is set in the session
if (isset($_SESSION['jwt_token'])) {
    error_log('JWT token found in session');
    $api_connector = new ApiConnector('');
    $jwt_token = $_SESSION['jwt_token'];
    $decoded_token = $api_connector->verify_jwt($jwt_token);

    if ($decoded_token) {
        // Token is valid, redirect to the dashboard page
        error_log('Token valid, redirecting to dashboard');
        wp_redirect(home_url('/itaiassistant101/dashboard'));
        exit();
    } else {
        error_log('Token invalid');
    }
} else {
    error_log('No JWT token in session');
}

// Display the forms
?>
<h1>Login</h1>

<!-- Teacher Form -->
<h2>Register</h2>
<form method="POST">
    <label for="api_key">API Key (For Teacher Registration):</label>
    <input type="text" id="api_key" name="api_key">
    <br>
    <label for="user_name">Name:</label>
    <input type="text" id="user_name" name="user_name" required>
    <br>
    <label for="user_surname">Surname:</label>
    <input type="text" id="user_surname" name="user_surname" required>
    <br>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    <br>
    <input type="submit" name="form_register" value="Register">
</form>

<form method="POST" action="<?php echo home_url('/itaiassistant101/ResetTeacherPassword'); ?>">
    <input type="submit" value="Forgot password?">
</form>


<!--General Login Form -->
<h2>Login</h2>
<form method="POST">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required>
    <br>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    <br>
    <input type="submit" name="general_login" value="Log in">
</form>
