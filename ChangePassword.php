<?php
session_start();
require_once 'UserManager.php';
require_once 'APIConnector.php';

$api_connector = new ApiConnector('');
$user_manager = new UserManager();

if (isset($_SESSION['jwt_token'])) {
    $jwt_token = $_SESSION['jwt_token'];
    echo ''. $jwt_token .'';
    $decoded_token = $api_connector->verify_jwt($jwt_token);

    if (!$decoded_token) {
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
$user = $user_manager->get_user_by_username($username);
if ($user->temporary_password == '') {
    wp_redirect(home_url('/itaiassistant101/index'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $user_manager->update_password($username, $new_password);
    wp_redirect(home_url('/itaiassistant101/index'));
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="max-width: 500px; margin-top: 50px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">

    <h1>Change Password</h1>
    <form method="POST">
        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>

</div>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
    
