<?php
/*
Plugin Name: ITAI Assistant 101
Description: A simple plugin for teacher authentication using API key.
Version: 1.0
Author: Your Name
*/

// Create new rewrite rules
function itaiassistant101_rewrite_rule() {
    add_rewrite_rule('^itaiassistant101/?$', 'index.php?itaiassistant101_login=1', 'top');
    add_rewrite_rule('^itaiassistant101/dashboard/?', 'index.php?itaiassistant101_dashboard=1', 'top');
    add_rewrite_rule('^itaiassistant101/login/?', 'index.php?itaiassistant101_login=1', 'top');
    add_rewrite_rule('^itaiassistant101/logout/?', 'index.php?itaiassistant101_logout=1', 'top');
}
add_action('init', 'itaiassistant101_rewrite_rule');

// Add new query vars
function itaiassistant101_query_vars($vars) {
    $vars[] = 'itaiassistant101_dashboard';
    $vars[] = 'itaiassistant101_login';
    $vars[] = 'itaiassistant101_logout';
    return $vars;
}
add_filter('query_vars', 'itaiassistant101_query_vars');

// Redirect to the appropriate template
function itaiassistant101_template_include($template) {
    if (get_query_var('itaiassistant101_dashboard')) {
        return plugin_dir_path(__FILE__) . 'dashboard.php';
    } elseif (get_query_var('itaiassistant101_login')) {
        return plugin_dir_path(__FILE__) . 'login.php';
    } elseif (get_query_var('itaiassistant101_logout')) { // Add this block
        return plugin_dir_path(__FILE__) . 'logout.php';
    }
    return $template;
}
add_filter('template_include', 'itaiassistant101_template_include');

function generate_and_store_secret_key() {
    error_log('generate_and_store_secret_key called'); // Debug statement
    $secret_key = bin2hex(random_bytes(32)); // Generate a random 32-byte key
    update_option('it_ai_assistant_secret_key', $secret_key); // Use update_option to ensure the key is set
}
register_activation_hook(__FILE__, 'generate_and_store_secret_key');

// Manual check and call to ensure the secret key is set
function ensure_secret_key() {
    if (!get_option('it_ai_assistant_secret_key')) {
        generate_and_store_secret_key();
    }
}
add_action('init', 'ensure_secret_key');

function get_secret_key() {
    $secret_key = get_option('it_ai_assistant_secret_key');
    if (!$secret_key) {
        throw new Exception('Secret key is not set.');
    }
    return $secret_key;
}

function create_student_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tbit_ai_assistant101_student';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $table_name (
            student_name varchar(255) NOT NULL,
            student_surname varchar(255) NOT NULL,
            student_username varchar(255) NOT NULL UNIQUE,
            student_password varchar(255) NOT NULL,
            PRIMARY KEY  (student_username)
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

register_activation_hook(__FILE__, 'create_student_table');
?>
