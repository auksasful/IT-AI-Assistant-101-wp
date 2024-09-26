<?php
session_start();
require_once 'APIConnector.php';
require_once 'UserManager.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reset_password'])) {
        $api_key = sanitize_text_field($_POST['api_key']);
        $new_password = sanitize_text_field($_POST['new_password']);

        $api_connector = new ApiConnector($api_key);
        $user_manager = new UserManager();

        $user = $user_manager->get_user_by_api_key($api_key);

        if (!$user) {
            echo 'Invalid API key.';
        }

        $user_manager->reset_password($user->user_username, $new_password);

        wp_redirect(home_url('/itaiassistant101/login'));
        exit();
    }
}
?>

<h1>Reset Teacher Password</h1>
<form method="POST">
    <label for="api_key">API Key:</label>
    <input type="text" id="api_key" name="api_key" required>
    <br>
    <label for="new_password">New Password:</label>
    <input type="password" id="new_password" name="new_password" required>
    <br>
    <input type="submit" name="reset_password" value="Reset Password">
</form>
