<?php

/*
Plugin Name: IT AI Assistant For Lithuania
Description: A plugin that is used for data analysis teaching for Lithuanian schools.
Version: 1.02
Author: Tomas Staškevičius
*/

// Create new rewrite rules
function itaiassistant101_rewrite_rule() {
    add_rewrite_rule('^itaiassistant101/?$', 'index.php?itaiassistant101_login=1', 'top');
    add_rewrite_rule('^itaiassistant101/login/?', 'index.php?itaiassistant101_login=1', 'top');
    add_rewrite_rule('^itaiassistant101/logout/?', 'index.php?itaiassistant101_logout=1', 'top');
    add_rewrite_rule('^itaiassistant101/index/?', 'index.php?itaiassistant101_index=1', 'top');
    add_rewrite_rule('^itaiassistant101/joinclass/?', 'index.php?itaiassistant101_joinclass=1', 'top');
    add_rewrite_rule('^itaiassistant101/faq/?', 'index.php?itaiassistant101_faq=1', 'top');
    add_rewrite_rule('^itaiassistant101/changepw/?', 'index.php?itaiassistant101_changepw=1', 'top');
    
}
function itaiassistant101_activate() {
    itaiassistant101_rewrite_rule();
    flush_rewrite_rules();
    error_log('ITAI Assistant 101 Plugin Activated and Rewrite Rules Flushed');
}
register_activation_hook(__FILE__, 'itaiassistant101_activate');

add_action('init', 'itaiassistant101_rewrite_rule');

// Add new query vars
function itaiassistant101_query_vars($vars) {
    $vars[] = 'itaiassistant101_login';
    $vars[] = 'itaiassistant101_logout';
    $vars[] = 'itaiassistant101_index';
    $vars[] = 'itaiassistant101_joinclass';
    $vars[] = 'itaiassistant101_faq';
    $vars[] = 'itaiassistant101_changepw';
    return $vars;
}
add_filter('query_vars', 'itaiassistant101_query_vars');

// Redirect to the appropriate template
function itaiassistant101_template_include($template) {
    if (get_query_var('itaiassistant101_login')) {
        error_log('itaiassistant101_login query var detected');
        return plugin_dir_path(__FILE__) . 'login.php';
    } elseif (get_query_var('itaiassistant101_logout')) {
        return plugin_dir_path(__FILE__) . 'logout.php';
    } elseif (get_query_var('itaiassistant101_index')) { 
        return plugin_dir_path(__FILE__) . 'index.php';
    } elseif (get_query_var('itaiassistant101_joinclass')) { 
        return plugin_dir_path(__FILE__) . 'joinclass.php';
    } elseif (get_query_var('itaiassistant101_faq')) { 
        return plugin_dir_path(__FILE__) . 'faq.php';
    } elseif (get_query_var('itaiassistant101_changepw')) { 
        return plugin_dir_path(__FILE__) . 'changepw.php';
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
            task_file_clean_pdf varchar(255),
            task_file_correct_pdf varchar(255),
            python_data_file varchar(255),
            orange_data_file varchar(255),
            task_file_uri varchar(255),
            clean_task_file_uri varchar(255),
            task_file_clean_pdf_uri varchar(255),
            task_file_correct_pdf_uri varchar(255),
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
            solution_file_pdf varchar(255),
            solution_file_uri varchar(255),
            solution_file_pdf_uri varchar(255),
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
    if (!defined('ABSPATH')) {
        require_once(dirname(__FILE__) . '/../../../wp-load.php');
    }
    
    $source_dir = dirname(__FILE__) . '/default_student_tasks';
    $destination_dir = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks';

    if (!file_exists($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }

    $files = scandir($source_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $source_file = "{$source_dir}/{$file}";
            $destination_file = "{$destination_dir}/{$file}";
            if (!copy($source_file, $destination_file)) {
                error_log("Failed to copy {$source_file} to {$destination_file}");
            } else {
                unlink($source_file);
            }
        }
    }
}

if (function_exists('register_activation_hook')) {
    register_activation_hook(__FILE__, 'move_default_student_tasks');
}

function move_icons() {
    if (!defined('ABSPATH')) {
        require_once(dirname(__FILE__) . '/../../../wp-load.php');
    }
    
    $source_dir = dirname(__FILE__) . '/icons';
    $destination_dir = WP_CONTENT_DIR . '/ITAIAssistant101/icons';

    if (!file_exists($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }

    $files = scandir($source_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $source_file = "{$source_dir}/{$file}";
            $destination_file = "{$destination_dir}/{$file}";
            if (!copy($source_file, $destination_file)) {
                error_log("Failed to copy {$source_file} to {$destination_file}");
            } else {
                unlink($source_file);
            }
        }
    }
}

if (function_exists('register_activation_hook')) {
    register_activation_hook(__FILE__, 'move_icons');
}


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


add_action('template_redirect', function() {
    if (
        isset($_GET['itaiassistant101_download_task_data']) &&
        isset($_GET['classId']) //&&
        //isset($_GET['username'])
    ) {
        require_once 'ClassManager.php';
        require_once 'TaskManager.php';
        require_once 'APIConnector.php';
        require_once 'UserManager.php';
        $classManager = new ClassManager();
        $user_manager = new UserManager();
        $taskManager = new TaskManager();
        $api_connector = new ApiConnector('');
        session_start();
        if (isset($_SESSION['jwt_token'])) {
            $jwt_token = $_SESSION['jwt_token'];
            $decoded_token = $api_connector->verify_jwt($jwt_token);
            if ($decoded_token) {
                $username = $decoded_token->data->username;
                $userType = $decoded_token->data->user_type;
                $current_user = $user_manager->get_user_by_username($username);
                $class_id = $current_user->last_used_class_id;
                if (!$class_id) {
                    wp_redirect(home_url('/itaiassistant101/joinclass'));
                    exit();
                }
                $message = home_url('/itaiassistant101');
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

        $class_id = $_GET['classId'];
        $class = $classManager->get_class_by_id($class_id);
        if ($class->class_main_teacher != $username) {
            http_response_code(403);
            exit;
        }

        $result = $taskManager->get_user_class_task_files_for_export($class_id);
        $task_files = $result['task_files'];
        $files = $result['files'];

        // Create temporary directory with unique name
        $temp_dir = sys_get_temp_dir() . '/task_export_' . uniqid();
        if (!mkdir($temp_dir, 0700, true)) {
            throw new Exception("Failed to create directory: {$temp_dir}");
        }
        $temp_dir = realpath($temp_dir); // Normalize path

        // Create JSON file
        $json_file = "{$temp_dir}/task_data.json";
        $json_data = json_encode($task_files, JSON_THROW_ON_ERROR);
        if (file_put_contents($json_file, $json_data) === false) {
            throw new Exception("Failed to write JSON file");
        }

        // Copy files to temp directory
        foreach ($files as $file) {
            if (!file_exists($file)) {
                error_log("Source file missing: {$file}");
                continue;
            }
            $dest = "{$temp_dir}/" . basename($file);
            if (!copy($file, $dest)) {
                throw new Exception("Failed to copy {$file} to {$dest}");
            }
        }

        // Create ZIP archive
        $zip_file = "{$temp_dir}.zip";
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot open ZIP: {$zip_file}");
        }

        // Add files to ZIP
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($temp_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($temp_dir) + 1);
            $relative_path = str_replace('\\', '/', $relative_path); // Ensure UNIX-style paths

            if (!$zip->addFile($file_path, $relative_path)) {
                throw new Exception("Failed to add {$relative_path} to ZIP");
            }
        }

        if (!$zip->close()) {
            throw new Exception("Failed to finalize ZIP");
        }

        // Verify ZIP file exists
        if (!file_exists($zip_file)) {
            throw new Exception("ZIP file not created");
        }

        // Send ZIP to client
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="tasks_export.zip"');
        header('Content-Length: ' . filesize($zip_file));
        header('Pragma: no-cache');
        
        // Clear output buffer and send file
        ob_end_clean();
        readfile($zip_file);

        // Cleanup
        array_map('unlink', glob("{$temp_dir}/*.*"));
        rmdir($temp_dir);
        unlink($zip_file);
        exit;
    }
});

function itaiassistant101_add_admin_menu() {
    add_options_page(
        'ITAI Assistant 101 Settings',
        'ITAI Assistant 101',
        'manage_options',
        'itaiassistant101_settings',
        'itaiassistant101_settings_page'
    );
}
add_action('admin_menu', 'itaiassistant101_add_admin_menu');

function itaiassistant101_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    if (!function_exists('site_url')) {
        require_once(ABSPATH . 'wp-includes/link-template.php');
    }
    $iframe_src = site_url('/itaiassistant101/login/');
    $faq_link = site_url('/itaiassistant101/faq/');
    ?>
    <div class="wrap">
        <h1>ITAI Assistant 101 - Instructions</h1>
        <p>Copy this code to embed the tool inside any page of the Wordpress site:</p>
        <code>
&lt;iframe src="<?php echo esc_url($iframe_src); ?>" width="100%" height="650" frameborder="0" allowfullscreen&gt;&lt;/iframe&gt;
        </code>
        <p>FAQ link: <a href="<?php echo esc_url($faq_link); ?>" target="_blank">View FAQ</a></p>
        <!-- tool available at: -->
        <p>Tool available at: <a href="<?php echo esc_url($iframe_src); ?>" target="_blank"><?php echo esc_url($iframe_src); ?></a></p>
    </div>
    <?php
}
