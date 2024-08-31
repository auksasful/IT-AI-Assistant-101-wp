<?php
require 'UserManager.php';
$user_manager = new UserManager();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['student_username'];
    $user_manager->create_temporary_password($username);
    // Redirect back to the user list or show a success message
    home_url('/itaiassistant101/userlist');
    exit();
}
?>
