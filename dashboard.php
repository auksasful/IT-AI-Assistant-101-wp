<?php
session_start();

// Include the file containing the class
require 'APIConnector.php';
require 'UserManager.php';

// Instantiate the class (no need for API key here)
$api_connector = new ApiConnector('');
// $data_encryption = new ITAIAssistant_Data_Encryption();
$user_manager = new UserManager();

$teacher_user_empty = true;

// Check if the JWT token is set in the session
if (isset($_SESSION['jwt_token'])) {
    $jwt_token = $_SESSION['jwt_token'];
    echo ''. $jwt_token .'';
    $decoded_token = $api_connector->verify_jwt($jwt_token);

    if ($decoded_token) {
        // Token is valid, proceed with the page
        echo 'Welcome to the dashboard!';
    } else {
        // Token is invalid, redirect to login
        wp_redirect(home_url('/itaiassistant101/login'));
        exit();
    }
} else {
    // No token found, redirect to login
    wp_redirect(home_url('/itaiassistant101/login'));
    exit();
}

$username = $decoded_token->data->username;
$userType = $decoded_token->data->user_type;

echo 'Current user: ' . $username . ' (' . $userType . ')';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($userType == 'teacher') {
        /*if (isset($_POST['add_student'])) {
            $name = sanitize_text_field($_POST['student_name']);
            $surname = sanitize_text_field($_POST['student_surname']);
            $credentials = $user_manager->insert_user_with_generated_credentials($name, $surname, 'student', '', '', $username);
            echo 'Student added successfully! Username: ' . $credentials['username'] . ' Password: ' . $credentials['password'];
        }
        elseif (isset($_POST['add_related_teacher'])) {
            $name = sanitize_text_field($_POST['related_teacher_name']);
            $surname = sanitize_text_field($_POST['related_teacher_surname']);
            $credentials = $user_manager->insert_user_with_generated_credentials($name, $surname, 'teacher', '', '', $username);
            echo 'Teacher added successfully! Username: ' . $credentials['username'] . ' Password: ' . $credentials['password'];
        }
        else*/if (isset($_POST['change_api_key'])) {
            $new_api_key = sanitize_text_field($_POST['new_api_key']);
            if ($user_manager->update_user_api_key($username, $new_api_key)) {
                echo 'API key changed successfully!';
            }
            else {
                echo 'Failed to change API key';
            }
        }
    }
    elseif ($userType == 'student') {
        if (isset($_POST['join_class'])) {
            $teacher_username = sanitize_text_field($_POST['assign_teacher_username']);
            $teacher_user = $user_manager->get_user_by_username($teacher_username);
            if ($teacher_user) {
                $user_manager->update_user_tied_request($username, $teacher_username, true);
                echo 'Request sent to ' . $teacher_user->user_username . ' (' . $teacher_user->user_name . ' ' . $teacher_user->user_surname . ')';
            }
            else {
                echo 'Teacher not found';
            }
        }
    }
}


$current_user = $user_manager->get_user_by_username($username);
if ($current_user->temporary_password != '') {
    echo 'You need to change your password';
    // Redirect to the change password page
    wp_redirect(home_url('/itaiassistant101/ChangePassword'));
    exit();
}

if ($userType == 'student') {
    $teacher_user = $user_manager->get_user_by_username($current_user->tied_to_teacher);
    echo 'Your teacher is: ' . $teacher_user->user_username;
    if ($teacher_user) {
        $teacher_user_empty = false;
    }
    // $api_key = $data_encryption->decrypt($teacher_user->api_key);
    $api_key = $user_manager->decrypt($teacher_user->api_key);
    echo 'Your api key is: ' . $api_key;
}
elseif ($userType == 'teacher' && $current_user->api_key == '') {
    if ($current_user->tied_to_teacher != '') {
        $teacher_user = $user_manager->get_user_by_username($current_user->tied_to_teacher);
        if ($teacher_user) {
            $teacher_user_empty = false;
        }
        echo 'Your master teacher is: ' . $teacher_user->user_username;
        // $api_key = $data_encryption->decrypt($teacher_user->api_key);
        $api_key = $user_manager->decrypt($teacher_user->api_key);
        echo 'Your api key is: ' . $api_key;
    }
    else {
        echo 'You do not have any students';
    }
}
else {
    echo 'You are a teacher';
    // $api_key = $data_encryption->decrypt($current_user->api_key);
    $api_key = $user_manager->decrypt($current_user->api_key);
    echo 'Your api key is: ' . $api_key;
}
?>

<h1>Dashboard</h1>
<!--
<?php //if ($userType == 'teacher'): ?>

<h2>Add Student</h2>
<form method="POST">
    <label for="student_name">Student Name:</label>
    <input type="text" id="student_name" name="student_name" required>
    <br>
    <label for="student_surname">Student Surname:</label>
    <input type="text" id="student_surname" name="student_surname" required>
    <br>
    <input type="submit" name="add_student" value="Add Student">
</form>
<br>

<?php ///*else*/if ($userType == 'teacher' && $current_user->api_key != ''): ?>
<h2>Add Related Teacher</h2>
<form method="POST">
    <label for="related_teacher_name">Teacher Name:</label>
    <input type="text" id="related_teacher_name" name="related_teacher_name" required>
    <br>
    <label for="related_teacher_surname">Teacher Surname:</label>
    <input type="text" id="related_teacher_surname" name="related_teacher_surname" required>
    <br>
    <input type="submit" name="add_related_teacher" value="Add Related Teacher">
</form>
<br>
<?php //endif; ?>
-->
<?php if ($userType == 'teacher'): ?>
<h2>Change my API key</h2>
<!-- Add Student Form -->
<form method="POST">
    <label for="new_api_key">New API key</label>
    <input type="text" id="new_api_key" name="new_api_key" required>
    <br>
    <input type="submit" name="change_api_key" value="Change">
</form>
<br>
<?php endif; ?>

<!-- Logout Button -->
<form method="POST" action="<?php echo home_url('/itaiassistant101/logout'); ?>">
    <input type="submit" value="Logout">
</form>


<?php if ($userType == 'student' && $teacher_user_empty && $current_user->tied_request == ''): ?>
<h2>Join Class (Ask your teacher for their username)</h2>
<!-- Add Student Form -->
<form method="POST">
    <label for="assign_teacher_username">Teacher Username (ex. TomSta1...)</label>
    <input type="text" id="assign_teacher_username" name="assign_teacher_username" required>
    <br>
    <input type="submit" name="join_class" value="Join Class">
</form>
<br>
<?php elseif ($userType == 'student' && $current_user->tied_request != ''): ?>
<?php
    $tied_request_teacher = $user_manager->get_user_by_username($current_user->tied_request);
    echo 'You have requested to join ' . $tied_request_teacher->user_username . '\'s (' . $tied_request_teacher->user_name . ' ' . $tied_request_teacher->user_surname . ') class';
?>
<?php else: ?>
<!-- Class -->
<form method="POST" action="<?php echo home_url('/itaiassistant101/userlist'); ?>">
    <input type="submit" value="My class <?php $join_requests = $user_manager->get_tied_requests_count($current_user->user_username); if($join_requests != 0) { echo "(". $join_requests . " Join Requests)"; } ?>">
</form>

<?php endif; ?>

<?php
// Debug statement to check if the form is being submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log('Form submitted');
}
?>
