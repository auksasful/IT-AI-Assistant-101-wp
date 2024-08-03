<?php
session_start();
$_SESSION = array();
session_destroy();
error_log('Session destroyed');
wp_redirect(home_url('/itaiassistant101/login'));
exit();
?>
