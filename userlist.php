<?php
session_start();
require 'APIConnector.php';
require 'UserManager.php';

$api_connector = new ApiConnector('');
$user_manager = new UserManager();

if (isset($_SESSION['jwt_token'])) {
    $jwt_token = $_SESSION['jwt_token'];
    $decoded_token = $api_connector->verify_jwt($jwt_token);
    if ($decoded_token) {
        $username = $decoded_token->data->username;
        $userType = $decoded_token->data->user_type;
        $current_user = $user_manager->get_user_by_username($username);

        if ($userType == 'student') {
            $teacher_user = $current_user->tied_to_teacher;
        } else 
        {
            $teacher_user = $current_user->user_username;
        }
        
        echo '<h1>User List</h1>';
        echo '<table>';
        echo '<tr><th>Username</th><th>Name</th><th>Surname</th>';
        if ($userType == 'teacher') {
            echo '<th>Temporary Password</th><th>Reset Password</th><th>Application Key</th>';
        }
        echo '</tr>';

        $users = $user_manager->get_students_by_teacher($teacher_user);
        
        foreach ($users as $user) {
            error_log($user->user_username);
            echo '<tr>';
            echo '<td>' . $user->user_username . '</td>';
            echo '<td>' . $user->user_name . '</td>';
            echo '<td>' . $user->user_surname . '</td>';
            if ($userType == 'teacher' && ($user->user_role != 'teacher' || $user->tied_to_teacher == $current_user->user_username)) {
                echo '<td>' . $user_manager->decrypt($user->temporary_password) . '</td>';
                echo '<td><form method="POST" action="' . home_url('/itaiassistant101/HandleResetPassword') . '"><input type="hidden" name="student_username" value="' . $user->user_username  . '"><input type="submit" value="Reset Password"></form></td>';
                echo '<td>' . $user_manager->decrypt($user->application_key) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    } else {
        wp_redirect(home_url('/itaiassistant101/login'));
        exit();
    }
} else {
    wp_redirect(home_url('/itaiassistant101/login'));
    exit();
}
?>
