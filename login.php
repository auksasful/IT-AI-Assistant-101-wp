<?php

session_start();

require_once 'APIConnector.php';
require_once 'UserManager.php';

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
                echo "<script>alert('API key already in use')</script>";
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
            $credentials = $user_manager->insert_user_with_generated_credentials($name, $surname, 'teacher', $password, $api_key, '');
            $jwt_token = $api_connector->test_connection($credentials['username'], $credentials['user_role'], true);
            if(!$jwt_token){
                echo "<script>alert('Failed to connect to API')</script>";
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
                echo "<script>alert('API key already in use')</script>";
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
            $credentials = $user_manager->insert_user_with_generated_credentials($name, $surname, 'student', $password, $api_key, '');
            $jwt_token = $api_connector->test_connection($credentials['username'], $credentials['user_role'], false);
        }

        if ($jwt_token) {
            $_SESSION['jwt_token'] = $jwt_token;
            error_log('Redirecting to index');
            wp_redirect(home_url('/itaiassistant101/index'));
            exit();
        } else {
            $user_manager->delete_user($credentials['username']);
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
                $_SESSION['jwt_token'] = $jwt_token;
                error_log('Redirecting to index');
                wp_redirect(home_url('/itaiassistant101/index'));
                exit();
            } else {
                error_log('Redirecting to login (invalid token)');
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
        } else {
            error_log('Invalid username or password');
            echo "<script>alert('Invalid username or password')</script>";
                
            $login_attempts = $user_manager->track_login_attempt($username);
            if($login_attempts > 10){
                echo "<script>alert('Too many login attempts, wait 10 minutes')</script>";
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
        }
    }
}

if (isset($_SESSION['jwt_token'])) {
    error_log('JWT token found in session');
    $api_connector = new ApiConnector('');
    $jwt_token = $_SESSION['jwt_token'];
    $decoded_token = $api_connector->verify_jwt($jwt_token);

    if ($decoded_token) {
        error_log('Token valid, redirecting to index');
        wp_redirect(home_url('/itaiassistant101/index'));
        exit();
    } else {
        error_log('Token invalid');
    }
} else {
    error_log('No JWT token in session');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 500px;
            margin-top: 50px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .btn-primary {
            width: 100%;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
        }
        .btn-group .btn {
            width: 48%;
        }
        .required::after {
            content: " *";
            color: red;
        }
        .alert {
            margin-top: 20px;
        }
        #show_hide_api_key a {
            padding: 0.25em;
        }
    </style>
</head>
<body>

<div class="container">
    <div id="itaiassistant101_initialButtons" class="btn-group">
        <button id="itaiassistant101_showLogin" class="btn btn-primary">Login</button>
        <button id="itaiassistant101_showRegister" class="btn btn-secondary">Register</button>
        <button id="itaiassistant101_showChangePassword" class="btn btn-link">Forgot Password?</button>
    </div>

    <div id="itaiassistant101_registerForm" style="display: none;">
        <h2>Register</h2>
        <form method="POST">
            <div class="form-group">
                <label for="api_key">API Key (For Teacher Registration):</label>
                <div class="input-group" id="show_hide_api_key">
                    <input class="form-control" id="api_key" name="api_key" type="password">
                    <div class="input-group-addon">
                        <a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="user_name">Name:</label>
                <input type="text" id="user_name" name="user_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="user_surname">Surname:</label>
                <input type="text" id="user_surname" name="user_surname" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" name="form_register">Register</button>
        </form>
        <button id="itaiassistant101_showLoginFromRegister" class="btn btn-link">Already have an account? Login</button>
        <button id="itaiassistant101_showChangePasswordFormRegister" class="btn btn-link">Forgot Password?</button>
    </div>

    <div id="itaiassistant101_loginForm" style="display: none;">
        <h2>Login</h2>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" name="general_login">Log in</button>
        </form>
        <button id="itaiassistant101_showRegisterFromLogin" class="btn btn-link">Don't have an account? Register</button>
        <button id="itaiassistant101_showChangePasswordFormLogin" class="btn btn-link">Forgot Password?</button>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
    $(document).ready(function() {

        // $('form').on('submit', function(event) {
        //     event.preventDefault();
        //     var form = $(this);
        //     $.ajax({
        //         type: form.attr('method'),
        //         url: form.attr('action'),
        //         data: form.serialize(),
        //         success: function(response) {
        //             if (response.error) {
        //                 bootbox.alert(response.message);
        //             }
        //         },
        //         error: function() {
        //             bootbox.alert('An error occurred. Please try again.');
        //         }
        //     });
        // });

        $('#itaiassistant101_showLogin').click(function() {
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_loginForm').show();
            $('#itaiassistant101_initialButtons').hide();
        });

        $('#itaiassistant101_showRegister').click(function() {
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_registerForm').show();
            $('#itaiassistant101_initialButtons').hide();
        });

        $('#itaiassistant101_showChangePassword').click(function() {
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_initialButtons').hide();
        });

        $('#itaiassistant101_showLoginFromRegister').click(function() {
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_loginForm').show();
        });

        $('#itaiassistant101_showLoginFromChangePassword').click(function() {
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_loginForm').show();
        });

        $('#itaiassistant101_showRegisterFromLogin').click(function() {
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_registerForm').show();
        });

        $('#itaiassistant101_showRegisterFromChangePassword').click(function() {
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_registerForm').show();
        });

        $('#itaiassistant101_showChangePasswordFormLogin').click(function() {
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_registerForm').hide();
        });

        $('#itaiassistant101_showChangePasswordFormRegister').click(function() {
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_loginForm').hide();
        });

        $("#show_hide_api_key a").on('click', function(event) {
            event.preventDefault();
            if($('#show_hide_api_key input').attr("type") == "text"){
                $('#show_hide_api_key input').attr('type', 'password');
                $('#show_hide_api_key i').addClass( "fa-eye-slash" );
                $('#show_hide_api_key i').removeClass( "fa-eye" );
            }else if($('#show_hide_api_key input').attr("type") == "password"){
                $('#show_hide_api_key input').attr('type', 'text');
                $('#show_hide_api_key i').removeClass( "fa-eye-slash" );
                $('#show_hide_api_key i').addClass( "fa-eye" );
            }
        });
    });
</script>
</body>
</html>
