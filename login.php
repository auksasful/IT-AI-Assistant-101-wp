<?php

session_start();

require_once 'APIConnector.php';
require_once 'UserManager.php';
require_once 'languageconfig.php';

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
                echo "<script>alert('" . $lang['api_key_already_in_use'] . "')</script>";
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
            $credentials = $user_manager->insert_user_with_generated_credentials($name, $surname, 'teacher', $password, $api_key, '');
            $jwt_token = $api_connector->test_connection($credentials['username'], $credentials['user_role'], true);
            if(!$jwt_token){
                echo "<script>alert('" . $lang['failed_to_connect_to_api'] . "')</script>";
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
                echo "<script>alert('" . $lang['api_key_already_in_use'] . "')</script>";
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
        }
        else {
            error_log('Invalid username or password');
            echo "<script>alert('" . $lang['invalid_username_or_password'] . "')</script>";
                
            $login_attempts = $user_manager->track_login_attempt($username);
            if($login_attempts > 10){
                echo "<script>alert('" . $lang['too_many_login_attempts'] . "')</script>";
                wp_redirect(home_url('/itaiassistant101/login'));
                exit();
            }
        }
    }
    elseif (isset($_POST['change_password'])) {
        error_log('Change password form submitted');
        $api_key = $_POST['change_password_api_key'];
        $api_connector = new ApiConnector($api_key);
        $jwt_token = $api_connector->test_connection('', '', false);
        $decoded_token = $api_connector->verify_jwt($jwt_token);
        if ($decoded_token) {
            $_SESSION['jwt_token'] = $jwt_token;
            error_log('Redirecting to index');
            $user_manager->create_temporary_password($decoded_token->data->username);
            wp_redirect(home_url('/itaiassistant101/changepw'));
            exit();
        } else {
            error_log('Redirecting to login (invalid token)');
            wp_redirect(home_url('/itaiassistant101/login'));
            exit();
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
        .required label {
            font-weight: bold;
        }
        .required label:after {
            color: #e32;
            content: ' *';
            display:inline;
        }
        .alert {
            margin-top: 20px;
        }
        #show_hide_api_key a {
            padding: 0.25em;
        }
        #bottom-task-info {
            position: sticky;
            bottom: 0;
            background-color: #F9FBD3;
            padding: 10px;
            border-top: 1px solid #dee2e6;
            margin-top: auto;
            width: 100%;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
            text-align: left;
            transition: background-color 0.3s ease;
            display: inline-block;
            text-align: center;
        }
        #bottom-task-info:hover {
            background-color:rgb(220, 255, 164);
        }
        .fa.fa-eye-slash {
            padding-top: 0.7rem;
            padding-left: 0.3rem;
        }
        .fa.fa-eye {
            padding-top: 0.7rem;
            padding-left: 0.3rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div id="itaiassistant101_initialButtons" class="btn-group">
        <button id="itaiassistant101_showLogin" class="btn btn-primary"><?php echo $lang['login'] ?></button>
        <button id="itaiassistant101_showRegister" class="btn btn-secondary"><?php echo $lang['register'] ?></button>
        </br>   
        <button id="itaiassistant101_showChangePassword" class="btn btn-link"><?php echo $lang['forgot_password'] ?></button>
    </div>

    <div id="itaiassistant101_registerForm" style="display: none;">
        <h2><?php echo $lang['register'] ?></h2>
        <form method="POST">
            <div class="form-group">
                <label for="api_key"><?php echo $lang['api_key_teacher'] ?></label>
                <div class="input-group" id="show_hide_api_key">
                    <input class="form-control" id="api_key" name="api_key" type="password">
                    <div class="input-group-addon">
                        <a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
            <div class="form-group required">
                <label for="user_name"><?php echo $lang['name'] ?></label>
                <input type="text" id="user_name" name="user_name" class="form-control" required>
            </div>
            <div class="form-group required">
                <label for="user_surname"><?php echo $lang['surname'] ?></label>
                <input type="text" id="user_surname" name="user_surname" class="form-control" required>
            </div>
            <div class="form-group required">
                <label for="password"><?php echo $lang['password'] ?></label>
                <div class="input-group" id="show_hide_register_password">
                    <input type="password" id="password" name="password" class="form-control" required>
                    <div class="input-group-addon">
                        <a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" name="form_register"><?php echo $lang['register'] ?></button>
        </form>
        <button id="itaiassistant101_showLoginFromRegister" class="btn btn-link"><?php echo $lang['already_have_account'] ?></button>
        <button id="itaiassistant101_showChangePasswordFormRegister" class="btn btn-link"><?php echo $lang['forgot_password'] ?></button>
    </div>

    <div id="itaiassistant101_loginForm" style="display: none;">
        <h2><?php echo $lang['login'] ?></h2>
        <form method="POST">
            <div class="form-group required">
                <label for="username"><?php echo $lang['username'] ?></label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group required">
                <label for="password"><?php echo $lang['password'] ?></label>
                <div class="input-group" id="show_hide_login_password">
                <input type="password" id="password" name="password" class="form-control" required>
                    <div class="input-group-addon">
                        <a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" name="general_login"><?php echo $lang['login'] ?></button>
        </form>
        <button id="itaiassistant101_showRegisterFromLogin" class="btn btn-link"><?php echo $lang['dont_have_account'] ?></button>
        <button id="itaiassistant101_showChangePasswordFormLogin" class="btn btn-link"><?php echo $lang['forgot_password'] ?></button>
    </div>
    <!-- change password form that takes api_key and password-->
    <div id="itaiassistant101_changePasswordForm" style="display: none;">
        <h2><?php echo $lang['forgot_password'] ?></h2>
        <form method="POST">
            <div class="form-group required">
                <label for="change_password_api_key"><?php echo $lang['api_key_only_teacher'] ?></label>
                <div class="input-group" id="show_hide_change_password_api_key">
                    <input type="text" id="change_password_api_key" name="change_password_api_key" class="form-control" required>
                    <div class="input-group-addon">
                        <a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" name="change_password"><?php echo $lang['change_password'] ?></button>
        </form>
        <button id="itaiassistant101_showLoginFromChangePassword" class="btn btn-link"><?php echo $lang['login'] ?></button>
        <button id="itaiassistant101_showRegisterFromChangePassword" class="btn btn-link"><?php echo $lang['register'] ?></button>
    </div>

    <br>
    <a id="bottom-task-info" href="<?php echo home_url('/itaiassistant101/faq'); ?>">
        <?php echo $lang['faq']; ?>
    </a>
    <br>
<div class="text-center">
    <a href="login.php?lang=en" onclick="event.preventDefault(); window.location.href='<?php echo home_url('/itaiassistant101/login?lang=en'); ?>';"><?php echo $lang['lang_en'] ?></a>
    | <a href="login.php?lang=lt" onclick="event.preventDefault(); window.location.href='<?php echo home_url('/itaiassistant101/login?lang=lt'); ?>';"><?php echo $lang['lang_lt'] ?></a>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
    $(document).ready(function() {

        $('#itaiassistant101_showLogin').click(function() {
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_loginForm').show();
            $('#itaiassistant101_initialButtons').hide();
            $('#itaiassistant101_changePasswordForm').hide();
        });

        $('#itaiassistant101_showRegister').click(function() {
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_registerForm').show();
            $('#itaiassistant101_initialButtons').hide();
            $('#itaiassistant101_changePasswordForm').hide();
        });

        $('#itaiassistant101_showChangePassword').click(function() {
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_initialButtons').hide();
            $('#itaiassistant101_changePasswordForm').show();
        });

        $('#itaiassistant101_showLoginFromRegister').click(function() {
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_loginForm').show();
            $('#itaiassistant101_initialButtons').hide();
            $('#itaiassistant101_changePasswordForm').hide();
        });

        $('#itaiassistant101_showLoginFromChangePassword').click(function() {
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_loginForm').show();
            $('#itaiassistant101_initialButtons').hide();
            $('#itaiassistant101_changePasswordForm').hide();
        });

        $('#itaiassistant101_showRegisterFromLogin').click(function() {
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_registerForm').show();
            $('#itaiassistant101_initialButtons').hide();
            $('#itaiassistant101_changePasswordForm').hide();
        });

        $('#itaiassistant101_showRegisterFromChangePassword').click(function() {
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_registerForm').show();
            $('#itaiassistant101_initialButtons').hide();
            $('#itaiassistant101_changePasswordForm').hide();
        });

        $('#itaiassistant101_showChangePasswordFormLogin').click(function() {
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_initialButtons').hide();
            $('#itaiassistant101_changePasswordForm').show();
        });

        $('#itaiassistant101_showChangePasswordFormRegister').click(function() {
            $('#itaiassistant101_registerForm').hide();
            $('#itaiassistant101_loginForm').hide();
            $('#itaiassistant101_initialButtons').hide();
            $('#itaiassistant101_changePasswordForm').show();
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

        $("#show_hide_register_password a").on('click', function(event) {
            event.preventDefault();
            if($('#show_hide_register_password input').attr("type") == "text"){
                $('#show_hide_register_password input').attr('type', 'password');
                $('#show_hide_register_password i').addClass( "fa-eye-slash" );
                $('#show_hide_register_password i').removeClass( "fa-eye" );
            }else if($('#show_hide_register_password input').attr("type") == "password"){
                $('#show_hide_register_password input').attr('type', 'text');
                $('#show_hide_register_password i').removeClass( "fa-eye-slash" );
                $('#show_hide_register_password i').addClass( "fa-eye" );
            }
        });

        $("#show_hide_login_password a").on('click', function(event) {
            event.preventDefault();
            if($('#show_hide_login_password input').attr("type") == "text"){
                $('#show_hide_login_password input').attr('type', 'password');
                $('#show_hide_login_password i').addClass( "fa-eye-slash" );
                $('#show_hide_login_password i').removeClass( "fa-eye" );
            }else if($('#show_hide_login_password input').attr("type") == "password"){
                $('#show_hide_login_password input').attr('type', 'text');
                $('#show_hide_login_password i').removeClass( "fa-eye-slash" );
                $('#show_hide_login_password i').addClass( "fa-eye" );
            }
        });

        $("#show_hide_change_password_api_key").on('click', function(event) {
            event.preventDefault();
            if($('#show_hide_change_password_api_key input').attr("type") == "text"){
                $('#show_hide_change_password_api_key input').attr('type', 'password');
                $('#show_hide_change_password_api_key i').addClass( "fa-eye-slash" );
                $('#show_hide_change_password_api_key i').removeClass( "fa-eye" );
            }else if($('#show_hide_change_password_api_key input').attr("type") == "password"){
                $('#show_hide_change_password_api_key input').attr('type', 'text');
                $('#show_hide_change_password_api_key i').removeClass( "fa-eye-slash" );
                $('#show_hide_change_password_api_key i').addClass( "fa-eye" );
            }
        });
    });
</script>
</body>
</html>
