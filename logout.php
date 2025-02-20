<?php
session_start();
$_SESSION = array();
session_destroy();
wp_redirect(home_url('/itaiassistant101/login'));
exit();
?>
