<?php
session_start();
require_once 'APIConnector.php';
require_once 'UserManager.php';

$api_connector = new ApiConnector(api_key: '');
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
        echo '<tr><th>Username</th><th>Name</th><th>Surname</th><th>Role</th>';
        if ($userType == 'teacher') {
            echo '<th>Temporary Password</th><th>Reset Password</th><th>Request</th><th>Actions</th>';
        }
        echo '</tr>';

        $users = $user_manager->get_students_by_teacher($teacher_user);
        
        foreach ($users as $user) {
            error_log($user->user_username);
            echo '<tr>';
            echo '<td>' . $user->user_username . '</td>';
            echo '<td>' . $user->user_name . '</td>';
            echo '<td>' . $user->user_surname . '</td>';
            echo '<td>' . $user->user_role . '</td>';
            if ($userType == 'teacher' && ($user->user_role != 'teacher' || $user->tied_to_teacher == $current_user->user_username)) {
                echo '<td>' . $user_manager->decrypt($user->temporary_password) . '</td>';
                echo '<td><form method="POST" action="' . home_url('/itaiassistant101/HandleResetPassword') . '"><input type="hidden" name="student_username" value="' . $user->user_username  . '"><input type="submit" value="Reset Password"></form></td>';
            }
            if ($userType == 'teacher' && $user->tied_request == $current_user->user_username)
            {
                echo '<td><form method="POST"><input type="hidden" name="request_username" value="' . $user->user_username  . '"><input type="submit" name="accept_request" value="Accept Request"></form></td>';
            }
            if ($userType == 'teacher' && $user->tied_to_teacher == $current_user->user_username && $user->user_role == 'student')
            {
                echo '<td><form method="POST"><input type="hidden" name="make_teacher_nm" value="' . $user->user_username  . '"><input type="submit" name="make_teacher" value="Make Teacher"></form></td>';
            }
            if ($userType == 'teacher' && $user->user_role == 'teacher' && $user->tied_to_teacher == $current_user->user_username)
            {
                echo '<td><form method="POST"><input type="hidden" name="make_student_nm" value="' . $user->user_username  . '"><input type="submit" name="make_student" value="Make Student"></form></td>';
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($userType == 'teacher') {
        //accept request
        if (isset($_POST['accept_request'])) {
            $request_username = sanitize_text_field($_POST['request_username']);
            $user_manager->update_user_tied_request($request_username, $current_user->user_username, false);
            wp_redirect(home_url('/itaiassistant101/userlist'));
            exit();
        }
        //make teacher
        if (isset($_POST['make_teacher'])) {
            $make_teacher_username = sanitize_text_field($_POST['make_teacher_nm']);
            $user_manager->make_teacher($make_teacher_username, $current_user->user_username);
            wp_redirect(home_url('/itaiassistant101/userlist'));
            exit();
        }
        //make student
        if (isset($_POST['make_student'])) {
            $make_student_username = sanitize_text_field($_POST['make_student_nm']);
            $user_manager->make_student($make_student_username);
            wp_redirect(home_url('/itaiassistant101/userlist'));
            exit();
        }
    }
}
?>
