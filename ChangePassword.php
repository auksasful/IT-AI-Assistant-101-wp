<?php
session_start();
require_once 'UserManager.php';
require_once 'APIConnector.php';

$api_connector = new ApiConnector('');

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
$temporaryPassword = $decoded_token->data->temporary_password;

if ($temporaryPassword != '') {
    // Redirect to the dashboard
    echo 'You need to change your password';
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_manager = new UserManager();
    
    $new_password = sanitize_text_field($_POST['new_password']);
    
    // Update the password
    $user_manager->update_password($username, $new_password);
    
    // Redirect to the dashboard
    wp_redirect(home_url('/itaiassistant101/dashboard'));
    exit();
}
?>

<h1>Change Password</h1>
<form method="POST">
    <label for="new_password">New Password:</label>
    <input type="password" id="new_password" name="new_password" required>
    <br>
    <input type="submit" value="Change Password">
</form>
