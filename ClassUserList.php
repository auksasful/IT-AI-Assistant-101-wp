<?php
session_start();
require 'APIConnector.php';
require_once 'ClassManager.php';
require_once 'UserManager.php';

$api_connector = new ApiConnector('');
$classManager = new ClassManager();
$user_manager = new UserManager();

if (isset($_SESSION['jwt_token'])) {
    error_log('jwt_token found');
    $jwt_token = $_SESSION['jwt_token'];
    $decoded_token = $api_connector->verify_jwt($jwt_token);
    if ($decoded_token) {
        error_log('Token valid');
        $username = $decoded_token->data->username;
        $userType = $decoded_token->data->user_type;
        $current_user = $user_manager->get_user_by_username($username);
        $class_id = $_GET['class_id'];
        if (!$class_id) {
            wp_redirect(home_url('/itaiassistant101/dashboard'));
            exit();
        }
    } 
    else {
        wp_redirect(home_url('/itaiassistant101/login'));
        exit();
    }
} 
else {
    wp_redirect(home_url('/itaiassistant101/login'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($userType == 'teacher') {
        if (isset($_POST['add_user'])) {
            $user_username = $_POST['user_username'];
            $classManager->insert_class_user($class_id, $user_username);
        }
        elseif (isset($_POST['remove_user'])) {
            $user_username = $_POST['user_username'];
            $classManager->remove_class_user($class_id, $user_username);
        }
    }
}

$class_users = $classManager->get_class_users($class_id); // Replace with the actual username
if ($userType == 'teacher') {
    $teachers_students = $user_manager->get_students_by_teacher($username);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class User List</title>
</head>
<body>
    <h1>Class User List</h1>
    <?php if ($userType == 'teacher'): ?>
    <form method="post">
        <label for="user_username">Select user:</label>
        <select name="user_username">
            <?php foreach ($teachers_students as $student): ?>
                <option value="<?php echo $student->user_username; ?>">
                    <?php echo htmlspecialchars($student->user_username); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="add_user">Add User</button>
    </form>
    <?php endif; ?>
    <h2>Existing Users in the class</h2>
    <ul>
        <?php foreach ($class_users as $class_user): ?>
            <li>
                <?php echo htmlspecialchars($class_user->user_username); ?>
                <?php if ($userType == 'teacher' && $class_user->user_username != $username): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="user_username" value="<?php echo $class_user->user_username; ?>">
                    <button type="submit" name="remove_user">Remove</button>
                </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
