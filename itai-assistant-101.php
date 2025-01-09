<?php
/*
Plugin Name: ITAI Assistant 101
Description: A simple plugin for teacher authentication using API key.
Version: 1.01
Author: Your Name
*/

// Create new rewrite rules
function itaiassistant101_rewrite_rule() {
    add_rewrite_rule('^itaiassistant101/?$', 'index.php?itaiassistant101_login=1', 'top');
    add_rewrite_rule('^itaiassistant101/dashboard/?', 'index.php?itaiassistant101_dashboard=1', 'top');
    add_rewrite_rule('^itaiassistant101/login/?', 'index.php?itaiassistant101_login=1', 'top');
    add_rewrite_rule('^itaiassistant101/logout/?', 'index.php?itaiassistant101_logout=1', 'top');
    add_rewrite_rule('^itaiassistant101/ChangePassword/?', 'index.php?itaiassistant101_ChangePassword=1', 'top');
    add_rewrite_rule('^itaiassistant101/userlist/?', 'index.php?itaiassistant101_userlist=1', 'top');
    add_rewrite_rule('^itaiassistant101/HandleResetPassword/?', 'index.php?itaiassistant101_HandleResetPassword=1', 'top');
    add_rewrite_rule('^itaiassistant101/ResetTeacherPassword/?', 'index.php?itaiassistant101_ResetTeacherPassword=1', 'top');
    add_rewrite_rule('^itaiassistant101/ClassList/?', 'index.php?itaiassistant101_ClassList=1', 'top');
    add_rewrite_rule('^itaiassistant101/ClassUserList/?', 'index.php?itaiassistant101_ClassUserList=1', 'top');
    add_rewrite_rule('^itaiassistant101/index/?', 'index.php?itaiassistant101_index=1', 'top');
}
add_action('init', 'itaiassistant101_rewrite_rule');

// Add new query vars
function itaiassistant101_query_vars($vars) {
    $vars[] = 'itaiassistant101_dashboard';
    $vars[] = 'itaiassistant101_login';
    $vars[] = 'itaiassistant101_logout';
    $vars[] = 'itaiassistant101_ChangePassword';
    $vars[] = 'itaiassistant101_userlist';
    $vars[] = 'itaiassistant101_HandleResetPassword';
    $vars[] = 'itaiassistant101_ResetTeacherPassword';
    $vars[] = 'itaiassistant101_ClassList';
    $vars[] = 'itaiassistant101_ClassUserList';
    $vars[] = 'itaiassistant101_index';
    return $vars;
}
add_filter('query_vars', 'itaiassistant101_query_vars');

// Redirect to the appropriate template
function itaiassistant101_template_include($template) {
    if (get_query_var('itaiassistant101_dashboard')) {
        return plugin_dir_path(__FILE__) . 'dashboard.php';
    } elseif (get_query_var('itaiassistant101_login')) {
        return plugin_dir_path(__FILE__) . 'login.php';
    } elseif (get_query_var('itaiassistant101_logout')) {
        return plugin_dir_path(__FILE__) . 'logout.php';
    } elseif (get_query_var('itaiassistant101_ChangePassword')) { 
        return plugin_dir_path(__FILE__) . 'ChangePassword.php';
    } elseif (get_query_var('itaiassistant101_userlist')) { 
        return plugin_dir_path(__FILE__) . 'userlist.php';
    } elseif (get_query_var('itaiassistant101_HandleResetPassword')) { 
        return plugin_dir_path(__FILE__) . 'HandleResetPassword.php';
    } elseif (get_query_var('itaiassistant101_ResetTeacherPassword')) { 
        return plugin_dir_path(__FILE__) . 'ResetTeacherPassword.php';
    } elseif (get_query_var('itaiassistant101_ClassList')) { 
        return plugin_dir_path(__FILE__) . 'ClassList.php';
    } elseif (get_query_var('itaiassistant101_ClassUserList')) { 
        return plugin_dir_path(__FILE__) . 'ClassUserList.php';
    } elseif (get_query_var('itaiassistant101_index')) { 
        return plugin_dir_path(__FILE__) . 'index.php';
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

function create_user_table() {
    error_log('create_user_table called'); // Debug statement
    global $wpdb;
    $table_name = $wpdb->prefix . 'it_ai_assistant101_user';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $table_name (
            user_name varchar(255) NOT NULL,
            user_surname varchar(255) NOT NULL,
            user_username varchar(255) NOT NULL UNIQUE,
            user_password varchar(255) NOT NULL,
            user_role varchar(255) NOT NULL,
            tied_to_teacher varchar(255),
            temporary_password varchar(255),
            api_key varchar(255),
            tied_request varchar(255),
            default_class_id int(11),
            last_used_class_id int(11),
            PRIMARY KEY  (user_username)
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log('Table created: ' . $table_name); // Debug statement
    } 
    else {
        error_log('Table already exists: ' . $table_name); // Debug statement
    }
}

function create_class_user_table() {
    error_log('create_class_user_table called'); // Debug statement
    global $wpdb;
    $table_name = $wpdb->prefix . 'it_ai_assistant101_class_user';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            class_id int(11) NOT NULL,
            user_username varchar(255) NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY (class_id) REFERENCES {$wpdb->prefix}it_ai_assistant101_class(class_id),
            FOREIGN KEY (user_username) REFERENCES {$wpdb->prefix}it_ai_assistant101_user(user_username)
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log('Table created: ' . $table_name); // Debug statement
    } 
    else {
        error_log('Table already exists: ' . $table_name); // Debug statement
    }
}

//create login attempts table
function create_login_attempts_table() {
    error_log('create_login_attempts_table called'); // Debug statement
    global $wpdb;
    $table_name = $wpdb->prefix . 'it_ai_assistant101_login_attempts';
    $trigger_name = $wpdb->prefix . 'it_ai_assistant101_delete_old_login_attempts';
    
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $table_name (
            attempt_id int(11) NOT NULL AUTO_INCREMENT,
            user_username varchar(255) NOT NULL,
            attempt_time datetime NOT NULL,
            PRIMARY KEY  (attempt_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log('Table created: ' . $table_name); // Debug statement        
    } 
    else {
        error_log('Table already exists: ' . $table_name); // Debug statement
    }
}

function create_class_table() {
    error_log('create_class_table called'); // Debug statement
    global $wpdb;
    $table_name = $wpdb->prefix . 'it_ai_assistant101_class';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $table_name (
            class_id int(11) NOT NULL AUTO_INCREMENT,
            class_name varchar(255) NOT NULL,
            class_main_teacher varchar(255) NOT NULL,
            class_creation_date datetime NOT NULL,
            PRIMARY KEY  (class_id),
            FOREIGN KEY (class_main_teacher) REFERENCES {$wpdb->prefix}it_ai_assistant101_user(user_username) ON DELETE CASCADE
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log('Table created: ' . $table_name); // Debug statement
    } 
    else {
        error_log('Table already exists: ' . $table_name); // Debug statement
    }
}

function create_task_table() {
    error_log('create_task_table called'); // Debug statement
    global $wpdb;
    $table_name = $wpdb->prefix . 'it_ai_assistant101_task';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $table_name (
            task_id int(11) NOT NULL AUTO_INCREMENT,
            task_name varchar(255) NOT NULL,
            task_text text NOT NULL,
            task_type varchar(255) NOT NULL,
            task_file_clean varchar(255),
            task_file_correct varchar(255),
            python_data_file varchar(255),
            orange_data_file varchar(255),
            task_file_uri varchar(255),
            clean_task_file_uri varchar(255),
            python_data_file_uri varchar(255),
            orange_data_file_uri varchar(255),
            python_program_execution_result text,
            orange_program_execution_result text,
            system_prompt text,
            default_summary text,
            default_self_check_questions text,
            class_id int(11) NOT NULL,
            PRIMARY KEY  (task_id),
            FOREIGN KEY (class_id) REFERENCES {$wpdb->prefix}it_ai_assistant101_class(class_id) ON DELETE CASCADE
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log('Table created: ' . $table_name); // Debug statement
    } 
    else {
        error_log('Table already exists: ' . $table_name); // Debug statement
    }
}



function create_student_task_solution_table() {
    error_log('create_student_task_solution_table called'); // Debug statement
    global $wpdb;
    $table_name = $wpdb->prefix . 'it_ai_assistant101_student_task_solution';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            task_id int(11) NOT NULL,
            class_id int(11) NOT NULL,
            user_username varchar(255) NOT NULL,
            solution_file varchar(255),
            solution_file_uri varchar(255),
            solution_file_mime_type varchar(255),
            PRIMARY KEY  (id),
            FOREIGN KEY (task_id) REFERENCES {$wpdb->prefix}it_ai_assistant101_task(task_id) ON DELETE CASCADE,
            FOREIGN KEY (user_username) REFERENCES {$wpdb->prefix}it_ai_assistant101_user(user_username) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES {$wpdb->prefix}it_ai_assistant101_class(class_id) ON DELETE CASCADE
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log('Table created: ' . $table_name); // Debug statement
    } 
    else {
        error_log('Table already exists: ' . $table_name); // Debug statement
    }
}

// create student task chat history table
function create_student_task_chat_history_table() {
    error_log('create_student_task_chat_history_table called'); // Debug statement
    global $wpdb;
    $table_name = $wpdb->prefix . 'it_ai_assistant101_student_task_chat_history';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            task_id int(11) NOT NULL,
            class_id int(11) NOT NULL,
            user_username varchar(255) NOT NULL,
            message_role varchar(255) NOT NULL,
            system_message text NOT NULL,
            user_message text NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY (task_id) REFERENCES {$wpdb->prefix}it_ai_assistant101_task(task_id) ON DELETE CASCADE,
            FOREIGN KEY (user_username) REFERENCES {$wpdb->prefix}it_ai_assistant101_user(user_username) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES {$wpdb->prefix}it_ai_assistant101_class(class_id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log('Table created: ' . $table_name); // Debug statement
    } else {
        error_log('Table already exists: ' . $table_name); // Debug statement
    }
}



function move_default_student_tasks() {
    $source_dir = plugin_dir_path(__FILE__) . 'default_student_tasks';
    $destination_dir = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks';

    if (!file_exists($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }

    $files = scandir($source_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $source_file = $source_dir . '/' . $file;
            $destination_file = $destination_dir . '/' . $file;
            if (!copy($source_file, $destination_file)) {
                error_log('Failed to copy ' . $source_file . ' to ' . $destination_file);
            } else {
                unlink($source_file);
            }
        }
    }
}

register_activation_hook(__FILE__, 'move_default_student_tasks');

function move_icons() {
    $source_dir = plugin_dir_path(__FILE__) . 'icons';
    $destination_dir = WP_CONTENT_DIR . '/ITAIAssistant101/icons';

    if (!file_exists($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }

    $files = scandir($source_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $source_file = $source_dir . '/' . $file;
            $destination_file = $destination_dir . '/' . $file;
            if (!copy($source_file, $destination_file)) {
                error_log('Failed to copy ' . $source_file . ' to ' . $destination_file);
            } else {
                unlink($source_file);
            }
        }
    }
}

register_activation_hook(__FILE__, 'move_icons');


register_activation_hook(__FILE__, 'create_user_table');
register_activation_hook(__FILE__, 'create_class_table');
register_activation_hook(__FILE__, 'create_class_user_table');
register_activation_hook(__FILE__, 'create_login_attempts_table');
register_activation_hook(__FILE__, 'create_task_table');
register_activation_hook(__FILE__, 'create_student_task_solution_table');
register_activation_hook(__FILE__, 'create_student_task_chat_history_table');

// Schedule the event on plugin activation
function schedule_delete_old_login_attempts() {
    if (!wp_next_scheduled('delete_old_login_attempts_event')) {
        wp_schedule_event(time(), 'hourly', 'delete_old_login_attempts_event');
    }
}
add_action('wp', 'schedule_delete_old_login_attempts');

// Hook the function to the event
add_action('delete_old_login_attempts_event', 'delete_old_login_attempts');

// Function to delete old login attempts
function delete_old_login_attempts() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'it_ai_assistant101_login_attempts';
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE attempt_time < %s",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        )
    );
}

// Clear the scheduled event on plugin deactivation
function unschedule_delete_old_login_attempts() {
    $timestamp = wp_next_scheduled('delete_old_login_attempts_event');
    wp_unschedule_event($timestamp, 'delete_old_login_attempts_event');
}
register_deactivation_hook(__FILE__, 'unschedule_delete_old_login_attempts');
?>
