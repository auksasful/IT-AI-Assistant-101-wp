<?php

session_start();

require 'APIConnector.php';

// Debug statement to check the script execution
error_log('login.php script executed');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log('Form submitted');
    $api_key = $_POST['api_key'];

    $api_connector = new ApiConnector($api_key);

    // Call the method to test the connection and handle the result
    $jwt_token = $api_connector->test_connection();

    if ($jwt_token) {
        // Store the JWT token in a session
        $_SESSION['jwt_token'] = $jwt_token;

        // Redirect to the dashboard page
        error_log('Redirecting to dashboard');
        wp_redirect(home_url('/itaiassistant101/dashboard'));
        exit();
    } else {
        // Redirect to the login page
        error_log('Redirecting to login (invalid token)');
        wp_redirect(home_url('/itaiassistant101/login'));
        exit();
    }
}

// Check if the JWT token is set in the session
if (isset($_SESSION['jwt_token'])) {
    error_log('JWT token found in session');
    $api_connector = new ApiConnector('');
    $jwt_token = $_SESSION['jwt_token'];
    $decoded_token = $api_connector->verify_jwt($jwt_token);

    if ($decoded_token) {
        // Token is valid, redirect to the dashboard page
        error_log('Token valid, redirecting to dashboard');
        wp_redirect(home_url('/itaiassistant101/dashboard'));
        exit();
    } else {
        error_log('Token invalid');
    }
} else {
    error_log('No JWT token in session');
}

// Display the form
?>
<form method="POST">
    <label for="api_key">API Key:</label>
    <input type="text" id="api_key" name="api_key">
    <input type="submit" value="Log in">
</form>
