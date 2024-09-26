<?php
session_start();
require_once 'APIConnector.php';
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
        if (isset($_POST['add_class'])) {
            $class_name = $_POST['class_name'];
            $classManager->insert_class($class_name, $username);
        } elseif (isset($_POST['delete_class'])) {
            $class_id = $_POST['class_id'];
            $classManager->remove_class($class_id);
        } elseif (isset($_POST['rename_class'])) {
            $class_id = $_POST['class_id'];
            $new_class_name = $_POST['new_class_name'];
            $classManager->edit_class($class_id, $new_class_name);
        }
    }
}

$class_users = $classManager->get_classes_by_username($username); // Replace with the actual username
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class List</title>
</head>
<body>
    <h1>Class List</h1>
    <?php if ($userType == 'teacher'): ?>
    <form method="post">
        <label for="class_name">Class Name:</label>
        <input type="text" id="class_name" name="class_name" required>
        <button type="submit" name="add_class">Add Class</button>
    </form>
    <?php endif; ?>
    <h2>Existing Classes</h2>
    <ul>
        <?php foreach ($class_users as $class): ?>
            <li>
                <?php echo htmlspecialchars($class->class_name); ?> - <?php echo htmlspecialchars($class->class_main_teacher); ?>
                <form method="post" style="display:inline;">
                    <?php if ($userType == 'teacher'): ?>
                        <input type="hidden" name="class_id" value="<?php echo $class->class_id; ?>">
                        <button type="submit" name="delete_class">Delete</button>
                        <input type="text" id="new_class_name" name="new_class_name">
                        <button type="submit" name="rename_class">Rename</button>
                    <?php endif; ?>
                    <a href="<?php echo home_url('/itaiassistant101/ClassUserList?class_id=' . $class->class_id); ?>">View Users</a>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
