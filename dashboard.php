<?php
session_start();

// Include the file containing the class
require 'APIConnector.php';
require 'UserManager.php';

// Instantiate the class (no need for API key here)
$api_connector = new ApiConnector('');
// $data_encryption = new ITAIAssistant_Data_Encryption();
$user_manager = new UserManager();

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

if ($userType == 'teacher') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['add_student'])) {
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
    // $api_key = $data_encryption->decrypt($teacher_user->api_key);
    $api_key = $user_manager->decrypt($teacher_user->api_key);
    echo 'Your api key is: ' . $api_key;
}
elseif ($userType == 'teacher' && $current_user->api_key == '') {
    if ($current_user->tied_to_teacher != '') {
        $teacher_user = $user_manager->get_user_by_username($current_user->tied_to_teacher);
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

<?php if ($userType == 'teacher'): ?>
<h2>Add Student</h2>
<!-- Add Student Form -->
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

<?php elseif ($userType == 'teacher' && $current_user->api_key != ''): ?>
<h2>Add Related Teacher</h2>
<!-- Add Student Form -->
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
<?php endif; ?>

<!-- Logout Button -->
<form method="POST" action="<?php echo home_url('/itaiassistant101/logout'); ?>">
    <input type="submit" value="Logout">
</form>

<!-- Logout Button -->
<form method="POST" action="<?php echo home_url('/itaiassistant101/userlist'); ?>">
    <input type="submit" value="My class">
</form>

<?php
// Debug statement to check if the form is being submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log('Form submitted');
}
?>
