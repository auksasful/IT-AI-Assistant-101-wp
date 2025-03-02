
<?php

session_start();
require_once 'APIConnector.php';
require_once 'ClassManager.php';
require_once 'UserManager.php';
require_once 'ExcelReader.php';
require_once 'GeminiManager.php';
require_once 'TaskManager.php';
require_once 'languageconfig.php';

$api_connector = new ApiConnector('');
$user_manager = new UserManager();
$current_task = "";
$taskManager = new TaskManager();
$username = '';
$classManager = new ClassManager();

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
        $temporary_password = $current_user->temporary_password;
        if ($temporary_password != '') {
            wp_redirect(home_url('/itaiassistant101/changepw'));
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

if(isset($_GET['task_id'])){
    $current_task = $taskManager->get_task($_GET['task_id'], $username);
}
else {
    $current_task = $lang['no_task_selected'];
}
// GEMINI API settings
$API_KEY = $user_manager->get_class_API_key($class_id);

function loadChatHistory(): void {
    global $current_task;
    global $username;
    $taskManager = new TaskManager();
    $chatHistory = $taskManager->get_student_task_chat_history($current_task->task_id, $current_task->class_id, $username);
    foreach ($chatHistory as $message) {
        $role = $message->message_role;
        // $content = htmlspecialchars($message->user_message, ENT_QUOTES, 'UTF-8');
        $content = json_encode($message->user_message);
        echo "displayMessage($content, \"$role\");";  }
}

function sendModelMessage($message) {
    // Retrieve conversation history from the session
    session_start();
    $messages = isset($_SESSION['messages']) ? $_SESSION['messages'] : [];
    // Add ChatGPT's response to the conversation history manually
    $messages[] = ['role' => 'model', 'content' => $message];

    // Save the conversation history to the session
    $_SESSION['messages'] = $messages;

    echo $message;
}


// Function to handle file uploads
function uploadFile($fileParam, $phpCall = false) {
    if (!defined('WP_CONTENT_DIR')) {
        define('WP_CONTENT_DIR', __DIR__);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($fileParam)) {
        global $username;
        global $API_KEY;
        global $current_task;
        $PDFReader = new GeminiManager($current_task->task_id, $current_task->class_id, $username);
        $folderPath = WP_CONTENT_DIR . "/ITAIAssistant101/$username/TASK" . $current_task->task_id;
        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        $file = $fileParam;
        $dateTime = date('YmdHis');
        $filePath = $folderPath . "/" . $username . "_" . $dateTime . "_" . basename($file['name']);
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        $tmpname = $file['tmp_name'];

        if ($phpCall) {
            if (copy($file['tmp_name'], $filePath)) {
                if ($_POST['message'] === 'python-data-file') {
                    $fileUri = $PDFReader->uploadFileNew($API_KEY, $filePath, "$fileName.txt", 'text/plain')[0];
                    return [$filePath, $fileUri];
                }
                if ($_POST['message'] === 'orange-data-file') {
                    return [$filePath, ''];
                }
                // if file extension is excel
                if ($fileExtension == 'xlsx' || $fileExtension == 'xls') {
                    $excel_reader = new ExcelReader($filePath);
                    $excel_data = $excel_reader->readDataWithCoordinates();
                    // move excel_data to text file with the same name to the same path but the extension is .txt
                    $textFilePath = str_replace($fileExtension, 'txt', $filePath);
                    $textFile = fopen($textFilePath, 'w');
                    fwrite($textFile, print_r($excel_data, true));
                    fclose($textFile);
                    $fileUri = $PDFReader->uploadFileNew($API_KEY, $textFilePath, "$fileName.txt", 'text/plain')[0];
                    return [$filePath, $fileUri];
                }
                elseif ($fileExtension == 'pdf') {
                    $fileUri = $PDFReader->uploadFileNew($API_KEY, $filePath, "$fileName.pdf", 'application/pdf')[0];
                    return [$filePath, $fileUri];
                }
                elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                    $result = $PDFReader->uploadFileNew($API_KEY, $filePath, "$fileName.$fileExtension", "image/$fileExtension");
                    return [$filePath, $result[0], $result[1]];
                }
                return [$filePath, ''];
            } else {
                return '';
            }
        } else {
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                if ($_POST['message'] === 'python-data-file') {
                    $fileUri = $PDFReader->uploadFileNew($API_KEY, $filePath, "$fileName.txt", 'text/plain')[0];
                    return [$filePath, $fileUri];
                }
                if ($_POST['message'] === 'orange-data-file') {
                    return [$filePath, ''];
                }
                // if file extension is excel
                if ($fileExtension == 'xlsx' || $fileExtension == 'xls') {
                    $excel_reader = new ExcelReader($filePath);
                    $excel_data = $excel_reader->readDataWithCoordinates();
                    // move excel_data to text file with the same name to the same path but the extension is .txt
                    $textFilePath = str_replace($fileExtension, 'txt', $filePath);
                    $textFile = fopen($textFilePath, 'w');
                    fwrite($textFile, print_r($excel_data, true));
                    fclose($textFile);
                    $fileUri = $PDFReader->uploadFileNew($API_KEY, $textFilePath, "$fileName.txt", 'text/plain')[0];
                    return [$filePath, $fileUri];
                }
                elseif ($fileExtension == 'pdf') {
                    $fileUri = $PDFReader->uploadFileNew($API_KEY, $filePath, "$fileName.pdf", 'application/pdf')[0];
                    return [$filePath, $fileUri];
                }
                elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                    $result = $PDFReader->uploadFileNew($API_KEY, $filePath, "$fileName.$fileExtension", "image/$fileExtension");
                    return [$filePath, $result[0], $result[1]];
                }
                return [$filePath, ''];
            } else {
                return '';
            }
        }
    } else {
        return 'Invalid request method or no file uploaded';
    }
}

function call_embedded_ocr_pdf($message, $fileUri = '', $saveToChatHistory = true) { 
    global $API_KEY;
    global $current_task;
    global $username;
    $pdfReader = new GeminiManager($current_task->task_id, $current_task->class_id, $username);
    echo $pdfReader->analyzePdf($API_KEY, $fileUri, $message, saveToChatHistory: $saveToChatHistory);
    return true;
}

function saveTask($name, $text, $type, $class_id, $file_clean = null, $file_correct = null, $python_data_file = null, $orange_data_file = null, $file_uri = null, $correct_file_uri = null, $python_data_file_uri = null, $orange_data_file_uri = null, $python_program_execution_result = null, $orange_program_execution_result = null, $system_prompt = null, $default_summary = null, $default_self_check_questions = null) {
    global $taskManager;
    return $taskManager->insert_task($name, $text, $type, $class_id, $file_clean, $file_correct, $python_data_file, $orange_data_file, $file_uri, $correct_file_uri, $python_data_file_uri, $orange_data_file_uri, $python_program_execution_result, $orange_program_execution_result, $system_prompt, $default_summary, $default_self_check_questions);
}

// get_tasks_by_class_id
function getTasksByClassId($class_id) {
    global $taskManager;
    global $username;
    return $taskManager->get_tasks_by_class_id($class_id, $username);
}

function getClassList() {
    global $classManager;
    global $username;
    return $classManager->get_classes_by_username($username);
}

function getClassUserList($class_id) {
    global $classManager;
    global $user_manager;
    global $username;
    $current_user = $user_manager->get_user_by_username($username);
    return $classManager->get_class_users($class_id, $username);
}

function removeClassUser($class_id, $username) {
    global $classManager;
    $classManager->remove_class_user($class_id, $username);
}

function acceptUserToClass($class_id, $user_id) {
    global $classManager;
    global $username;
    $classManager->insert_class_user($class_id, $user_id, $username);
}


function convert_path_to_url($full_path) {
    // Split the path to extract the domain name
    $path_parts = explode('/', $full_path);

    // Find the index of the domain part
    $domain_index = array_search('domains', $path_parts) + 1;
    
    // Extract the relevant path after the domain
    $relevant_path = array_slice($path_parts, $domain_index + 2); // +2 to skip 'domains' and the domain name itself

    // Construct the base URL with the domain
    $base_url = 'https://' . $path_parts[$domain_index] . '/';

    // Encode the relevant path parts
    $encoded_path = implode('/', array_map('rawurlencode', $relevant_path));

    // Construct the final URL
    $encoded_url = $base_url . $encoded_path;

    return $encoded_url;
}

function deleteFile($filePath) {
    global $username;
    if (strpos($filePath, $username) === false) {
        return;
    }
    if (file_exists($filePath)) {
        unlink($filePath);
        echo 'success';
    }
    else {
        echo 'file not found';
    }
}

function checkIfCurrentUserIsTeacher() : bool {
    global $user_manager;
    global $username;
    $current_user = $user_manager->get_user_by_username($username);
    return $current_user->user_role === 'teacher';
}


// Main execution logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input'); $data = json_decode($json, true);
    if ($json !== '') {
        $data = json_decode($json, true); 
        if (isset($data['message'])) { 
            $input = $data['message'];

            if($input === 'task-save') { 
                if (!checkIfCurrentUserIsTeacher()){
                    echo 'You are not authorized for the action';
                    exit();
                }
                // Access the data from the decoded JSON 
                $name = $data['name']; 
                $text = $data['text']; 
                $type = $data['type']; 
                $class_id = $data['class_id']; 
                $file_clean = $data['file_clean']; 
                $file_correct = $data['file_correct']; 
                $python_data_file = $data['python_data_file'];
                $orange_data_file = $data['orange_data_file'];
                $file_uri = $data['file_uri'];
                $correct_file_uri = $data['correct_file_uri'];
                $python_data_file_uri = $data['python_data_file_uri'];
                $orange_data_file_uri = $data['orange_data_file_uri'];
                $python_program_execution_result = $data['python_program_execution_result'];    
                $orange_program_execution_result = $data['orange_program_execution_result'];
                $system_prompt = 'You are a helpful and informative bot that answers questions in Lithuanian language using text from the file.'; 
                $default_summary = $data['default_summary']; 
                $default_self_check_questions = $data['default_self_check_questions']; 

                // Call the saveTask function with the data 
                echo json_encode(saveTask($name, $text, $type, $class_id, $file_clean, $file_correct, $python_data_file, $orange_data_file, $file_uri, $correct_file_uri, $python_data_file_uri, $orange_data_file_uri, $python_program_execution_result, $orange_program_execution_result, $system_prompt, $default_summary, $default_self_check_questions));
            }
            elseif($input === 'task-update') { 
                if (!checkIfCurrentUserIsTeacher()){
                    echo 'You are not authorized for the action';
                    exit();
                }
                $name = $data['name']; 
                $text = $data['text']; 
                $type = $data['type']; 
                $class_id = $data['class_id']; 
                $file_clean = $data['file_clean']; 
                $file_correct = $data['file_correct']; 
                $python_data_file = $data['python_data_file'];
                $orange_data_file = $data['orange_data_file'];
                $file_uri = $data['file_uri'];
                $correct_file_uri = $data['correct_file_uri'];
                $python_data_file_uri = $data['python_data_file_uri'];
                $orange_data_file_uri = $data['orange_data_file_uri'];
                $python_program_execution_result = $data['python_program_execution_result'];    
                $orange_program_execution_result = $data['orange_program_execution_result'];
                $system_prompt = 'You are a helpful and informative bot that answers questions in Lithuanian language using text from the file.'; 
                $default_summary = $data['default_summary']; 
                $default_self_check_questions = $data['default_self_check_questions']; 
                $class_id = $data['class_id']; 
                $task_id = $data['task_id'];
                $task_file_changed = $data['task_file_changed'];
                $correct_task_file_changed = $data['correct_task_file_changed'];
                $python_data_file_changed = $data['python_data_file_changed'];
                $orange_data_file_changed = $data['orange_data_file_changed'];
    
                $task = $taskManager->get_task($task_id, $username);
                if ($task_file_changed != 1) {
                    $file_uri = $task->task_file_uri;
                    $file_clean = $task->task_file_clean;
                }
                if ($correct_task_file_changed  != 1) {
                    $correct_file_uri = $task->clean_task_file_uri;
                    $file_correct = $task->task_file_correct;
                }
                if ($python_data_file_changed  != 1) {
                    $python_data_file_uri = $task->python_data_file_uri;
                    $python_data_file = $task->python_data_file;
                }
                if ($orange_data_file_changed  != 1) {
                    $orange_data_file_uri = $task->orange_data_file_uri;
                    $orange_data_file = $task->orange_data_file;
                }

                $taskManager->update_task(
                    $task_id,
                    $name,
                    $text,
                    $type,
                    $class_id,
                    $file_clean,
                    $file_correct,
                    $python_data_file,
                    $orange_data_file,
                    $file_uri,
                    $correct_file_uri,
                    $python_data_file_uri,
                    $orange_data_file_uri,
                    $python_program_execution_result,
                    $orange_program_execution_result,
                    $system_prompt,
                    $default_summary,
                    $default_self_check_questions
                );
            }
        }

    }


    if (isset($_POST['message'])) {
        $input = $_POST['message'];
        if ($input === 'intro-message') {
            if ($current_task->task_id != null) {
                sendModelMessage(" **{$lang["task_name"]}:**<br>{$current_task->task_name}<br>");
                sendModelMessage(" **{$lang["task_text"]}:**<br>{$current_task->task_text}<br>");
                if ($current_task->task_type == 'Python') {
                    sendModelMessage(" **{$lang["correct_program_execution_result"]}:**<br>" . $current_task->python_program_execution_result . "<br>");
                    sendModelMessage(" **{$lang["python_data_file"]}:**<br>" . convert_path_to_url($current_task->python_data_file));
                }
                elseif ($current_task->task_type == 'Orange') {
                    sendModelMessage(" **{$lang["correct_program_execution_result"]}:**<br>" . $current_task->orange_program_execution_result . "<br>");
                    sendModelMessage(" **{$lang["orange_data_file"]}:**<br>" . convert_path_to_url($current_task->orange_data_file));
                }
                sendModelMessage(" **{$lang["task_file"]}:**<br>" . convert_path_to_url($current_task->task_file_clean));
                if ($current_task->task_type == 'PDF') {
                    sendModelMessage(" **{$lang["task_summary"]}:**<br>{$current_task->default_summary}<br>");
                }
            }
            else {
                sendModelMessage($lang["no_task_selected"]);
            }
        }
        elseif ($input === 'task-summary') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $fileUri = urldecode($_POST['fileUri']);

            call_embedded_ocr_pdf('Please summarize the text in the PDF file in Lithuanian language', $fileUri, false);
        }
        elseif($input === 'task-questions') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $fileUri = urldecode($_POST['fileUri']);
            $pdfReader = new GeminiManager($current_task->task_id, $current_task->class_id, $username);
            $system_prompt = $prompts['task_questions_system_prompt'];
            $prompt = $prompts['task_questions_prompt'];
            echo $pdfReader->analyzePdfSelfCheck($API_KEY, $fileUri, $prompt, $system_prompt);
        }
        elseif($input === 'task-save') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            // Get the JSON data from the request body
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
        
            // Access the data from the decoded JSON
            $name = $data['name'];
            $text = $data['text'];
            $type = $data['type'];
            $class_id = $data['class_id'];
            $file_clean = $data['file_clean'];
            $file_correct = $data['file_correct'];
            $python_data_file = $data['python_data_file'];
            $orange_data_file = $data['orange_data_file'];
            $file_uri = $data['file_uri'];
            $correct_file_uri = $data['correct_file_uri'];
            $python_data_file_uri = $data['python_data_file_uri'];
            $orange_data_file_uri = $data['orange_data_file_uri'];
            $python_program_execution_result = $data['python_program_execution_result'];
            $orange_program_execution_result = $data['orange_program_execution_result'];
            $system_prompt = 'You are a helpful and informative bot that answers questions in lithuanian language using text from the file.';
            $default_summary = $data['default_summary'];
            $default_self_check_questions = $data['default_self_check_questions'];
        
            // Call the saveTask function with the data
            saveTask($name, $text, $type, $class_id, $file_clean, $file_correct, $python_data_file, $file_uri, $correct_file_uri, $python_data_file_uri, $python_program_execution_result, $system_prompt, $default_summary, $default_self_check_questions);
        }
        elseif($input === 'task-update') { 
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $name = $data['name']; 
            $text = $data['text']; 
            $type = $data['type']; 
            $class_id = $data['class_id']; 
            $file_clean = $data['file_clean']; 
            $file_correct = $data['file_correct']; 
            $python_data_file = $data['python_data_file'];
            $orange_data_file = $data['orange_data_file'];
            $file_uri = $data['file_uri'];
            $correct_file_uri = $data['correct_file_uri'];
            $python_data_file_uri = $data['python_data_file_uri'];
            $orange_data_file_uri = $data['orange_data_file_uri'];
            $python_program_execution_result = $data['python_program_execution_result'];    
            $orange_program_execution_result = $data['orange_program_execution_result'];
            $system_prompt = 'You are a helpful and informative bot that answers questions in Lithuanian language using text from the file.'; 
            $default_summary = $data['default_summary']; 
            $default_self_check_questions = $data['default_self_check_questions']; 
            $class_id = $data['class_id']; 
            $task_id = $data['task_id'];

            $taskManager->update_task(
                $task_id,
                $name,
                $text,
                $type,
                $class_id,
                $file_clean,
                $file_correct,
                null,
                null,
                $file_uri,
                $correct_file_uri,
                null,
                null,
                null,
                null,
                $system_prompt,
                $default_summary,
                $default_self_check_questions
            );
        }
        elseif($input === 'remove-task-file') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $fileName = $_POST['file-name'];
            $filePath = WP_CONTENT_DIR . "/ITAIAssistant101/" . $username . "/TASK/" . $fileName;
            deleteFile($filePath);
        }
        elseif($input === 'delete-task-files-on-close') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $currentTaskFilePath = $_POST['current-task-file-path'];
            $currentCorrectTaskFilePath = $_POST['current-correct-task-file-path'];
            $currentPythonDataFilePath = $_POST['current-python-data-file-path'];
            $currentOrangeDataFilePath = $_POST['current-orange-data-file-path'];
            $classId = $_POST['class-id'];

            // check if user is the main teacher of the class
            $class = $classManager->get_class_by_id($classId);
            if ($class->class_main_teacher != $username) {
                echo "error";
                exit();
            }
            
            deleteFile($currentTaskFilePath);
            deleteFile($currentCorrectTaskFilePath);
            deleteFile($currentPythonDataFilePath);
            deleteFile($currentOrangeDataFilePath);

        }
        elseif($input === 'delete-task') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $taskId = $_POST['task-id'];
            $classId = $_POST['class-id'];
            $the_task = $taskManager->get_task($taskId, $username);
            deleteFile($the_task->task_file_clean);
            deleteFile($the_task->task_file_correct);
            deleteFile($the_task->python_data_file);
            deleteFile($the_task->orange_data_file);
            return $taskManager->delete_task($taskId, $username, $classId);
        }
        elseif($input === 'task-list') {
            $class_id = $_POST['class_id'];
            $tasks = getTasksByClassId($class_id);
            echo json_encode($tasks);
        }
        elseif($input === 'class-list') {
            $classes = getClassList();
            // go through each class and check if the user is the main teacher
            // if yes, add property to the classes array use_actions as true
            // if no, add property to the classes array use_actions as false
            $current_user = $user_manager->get_user_by_username($username);
            foreach ($classes as $class) {
                if ($class->class_main_teacher == $username) {
                    $class->use_actions = true;
                }
                else {
                    $class->use_actions = false;
                }

                if ($current_user->default_class_id == $class->class_id) {
                    $class->is_default = true;
                }
                else {
                    $class->is_default = false;
                }

                if ($current_user->last_used_class_id == $class->class_id) {
                    $class->is_last_used = true;
                }
                else {
                    $class->is_last_used = false;
                }
            }
            echo json_encode($classes);
        }
        elseif($input === 'class-user-list')
        {
            $class_id = $_POST['class_id'];
            $users = getClassUserList($class_id);
            $class = $classManager->get_class_by_id($class_id);
            for ($i = 0; $i < count($users); $i++) {
                if ($users[$i]->user_username == $username) {
                    $users[$i]->is_current_user = true;
                }
                else {
                    $users[$i]->is_current_user = false;
                }
                if ($class->class_main_teacher == $users[$i]->user_username) {
                    $users[$i]->is_teacher = true;
                }
                else {
                    $users[$i]->is_teacher = false;
                }
                if ($users[$i]->user_role == 'student' && $users[$i]->temporary_password != '' && $users[$i]->temporary_password != null) {
                    $temporary_password = $users[$i]->temporary_password;
                    $decoded_temporary_password = $user_manager->get_decrypted_temporary_password($users[$i]->user_username);
                    $users[$i]->decoded_temporary_password = $decoded_temporary_password;
                }
                else {
                    $users[$i]->decoded_temporary_password = '';
                }
            }
            // Put the teacher at the beginning of the array, the current user in the middle and the students at the end
            $teacher = null;
            $current_user = null;
            $students = [];
            for ($i = 0; $i < count($users); $i++) {
                if ($users[$i]->is_teacher) {
                    $teacher = $users[$i];
                }
                elseif ($users[$i]->is_current_user) {
                    $current_user = $users[$i];
                }
                else {
                    $students[] = $users[$i];
                }
            }
            $users = [];
            if ($teacher) {
                $users[] = $teacher;
            }
            if ($current_user) {
                $users[] = $current_user;
            }
            $users = array_merge($users, $students);

            // remove user_password, api_key and temporary_password from the array
            for ($i = 0; $i < count($users); $i++) {
                unset($users[$i]->user_password);
                unset($users[$i]->api_key);
                unset($users[$i]->temporary_password);
            }

            echo json_encode($users);
        }
        elseif($input === 'reset-user-password'){
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $user_id = $_POST['user_id'];
            //check if $username is class main teacher
            $class_id = $_POST['classId'];
            $class = $classManager->get_class_by_id($class_id);
            if ($class->class_main_teacher != $username) {
                echo "error";
                return;
            }
            $user = $user_manager->get_user_by_username($user_id);
            if ($user_manager->create_temporary_password($user->user_username) != '') {
                echo "success";
            }
            else {
                echo "error";
            }
        }
        elseif($input === 'check-if-class-is-not-main-for-teacher')
        {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $class_id = $_POST['class_id'];
            if ($classManager->check_if_teacher_and_class_is_default($username, $class_id)) {
                echo "false";
            } else {
                echo "true"; 
            }
            exit();           
        }
        elseif($input === 'get-teacher-unadded-students')
        {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $class_id = $_POST['class_id'];
            // check if request is from the teacher of the class
            $class = $classManager->get_class_by_id($class_id);
            if ($class->class_main_teacher != $username) {
                echo json_encode([]);
                exit();
            }
            $students = $classManager->get_teacher_unadded_students($username, $class_id);
            echo json_encode($students);
        }
        elseif ($input === 'add-users-to-class') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $class_id = $_POST['class_id'];
            $users = $_POST['users'];
            $class = $classManager->get_class_by_id($class_id);
            if ($class->class_main_teacher != $username) {
                echo json_encode('not allowed');
                exit();
            }
            $users = json_decode(stripslashes($users));
            $classManager->add_users_to_class($class_id, $users);
            echo json_encode('success');
            exit();

        }
        elseif($input === 'delete-user-from-class')
        {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $class_id = $_POST['class_id'];
            $user_id = $_POST['user_id'];
            removeClassUser($class_id, $user_id);
        }
        elseif($input === 'accept-user-to-class')
        {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $class_id = $_POST['class_id'];
            $user_id = $_POST['user_id'];
            acceptUserToClass($class_id, $user_id);
        }
        elseif($input === 'change-password') {
            $old_password = sanitize_text_field($_POST['old_password']);
            $new_password = sanitize_text_field($_POST['new_password']);
            $user = $user_manager->get_user_by_username($username);
            if ($user && password_verify($old_password, $user->user_password)) {
                $jwt_token = $api_connector->test_connection($user->user_username, $user->user_role, false);

                if ($jwt_token) {
                    $user_manager->update_password($username, $new_password);
                    echo "success";
                    return true;
                } else {
                    echo "error";
                    return false;
                }
            } else {
                echo "error";
                return false;
            }
        }
        elseif($input === 'add-new-class') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $class_name = $_POST['class_name'];
            $result = $classManager->insert_class($class_name, $username, false);
            if ($result) {
                return json_encode('Class added successfully');
            }
            return json_encode('Class not added');
        }
        elseif ($input === 'delete-class') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $class_id = $_POST['class_id'];
            $class = $classManager->get_class_by_id($class_id);
            if ($class->class_main_teacher == $username) {
                $teacher_user = $user_manager->get_user_by_username($username);
                if ($teacher_user->default_class_id == $class_id) {
                    return false;
                }
                
                $tasks = $taskManager->get_tasks_by_class_id($class_id, $username);
                $classManager->remove_class(class_id: $class_id);
                foreach ($tasks as $task) {
                    deleteFile($task->task_file_clean);
                    deleteFile($task->task_file_correct);
                    deleteFile($task->python_data_file);
                    deleteFile($task->orange_data_file);
                }
                return true;
            }
            return false;
        }
        elseif ($input === 'edit-class') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $class_id = $_POST['class_id'];
            $class_name = $_POST['class_name'];
            $class = $classManager->get_class_by_id($class_id);
            if ($class->class_main_teacher == $username) {
                $teacher_user = $user_manager->get_user_by_username($username);
                if ($teacher_user->default_class_id == $class_id) {
                    return false;
                }
                
                $classManager->edit_class($class_id, $class_name);
                return true;
            }
            return false;
        }
        elseif($input === 'select-class') {
            $class_id = $_POST['class_id'];
            $user = $user_manager->get_user_by_username($username);
            $class_users = $classManager->get_class_users($class_id, $username);
            for ($i = 0; $i < count($class_users); $i++) {
                if ($class_users[$i]->user_username == $username) {
                    $classManager->set_last_used_class($username, $class_id);
                    return true;
                }
            }
            return false;
        }
        elseif($input === 'get-tied-requests-count') {
            // check if the user is a teacher
            $user = $user_manager->get_user_by_username($username);
            if ($user->user_role == 'teacher') {
                $count = $user_manager->get_tied_requests_count($username);
                echo $count > 0 ? $count : 'none';
            }
            else {
                echo 'none';
            }
        }
        elseif($input === 'import-tasks') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $class_id = $_POST['class_id'];
            // take the file as file
            $file = $_FILES['file'];
            // unzip the file and read task_data.json
            $zip = new ZipArchive;
            $res = $zip->open($file['tmp_name']);
            $temp_dir = WP_CONTENT_DIR . "/ITAIAssistant101/$username/TEMP/task_import_" . uniqid();
            if (!is_dir(dirname($temp_dir))) {
                mkdir(dirname($temp_dir), 0777, true);
            }
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0777, true);
            }

            // Extract the zip file
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) === TRUE) {
                $zip->extractTo($temp_dir);
                $zip->close();
                
                // Read the task_data.json file
                $json_file = $temp_dir . '/task_data.json';
                if (file_exists($json_file)) {
                    $task_data = json_decode(file_get_contents($json_file), true);
                    if ($task_data) {
                        foreach ($task_data as $task) {


                            $files_to_process = [
                                ['path' => $task['task_file_clean'], 'type' => 'task_file'],
                                ['path' => $task['task_file_correct'], 'type' => 'correct_file'],
                                ['path' => $task['python_data_file'], 'type' => 'python_data'],
                                ['path' => $task['orange_data_file'], 'type' => 'orange_data']
                            ];

                            $new_uris = [];
                            $new_paths = [];

                            $filesUploadError = false;

                            foreach ($files_to_process as $file_info) {
                                if (!empty($file_info['path'])) {
                                    $filename = basename($file_info['path']);
                                    $source_path = "{$temp_dir}/{$filename}";
                                    if (file_exists($source_path)) {
                                        $fileArray = [
                                            'name' => basename($source_path),
                                            'tmp_name' => $source_path,
                                            'type' => mime_content_type($source_path),
                                            'size' => filesize($source_path),
                                            'error' => 0
                                        ];
                                        $result = uploadFile($fileArray, true);
                                        
                                        if ($result) {
                                            // $upload_result = json_decode($result, true);
                                            $new_paths[$file_info['type']] = $result[0];
                                            $new_uris[$file_info['type']] = $result[1];
                                        }
                                        else {
                                            $filesUploadError = true;
                                        }
                                    }
                                    else {
                                        $filesUploadError = true;
                                    }
                                }
                            }
                            if ($filesUploadError) {
                                echo 'failed';
                                exit();
                            } else {
                            $taskManager->insert_task(
                                $task['task_name'],
                                $task['task_text'], 
                                $task['task_type'],
                                $class_id,
                                $new_paths['task_file'] ?? null,
                                $new_paths['correct_file'] ?? null,
                                $new_paths['python_data'] ?? null,
                                $new_paths['orange_data'] ?? null,
                                $new_uris['task_file'] ?? null,
                                $new_uris['correct_file'] ?? null,
                                $new_uris['python_data'] ?? null,
                                $new_uris['orange_data'] ?? null,
                                $task['python_program_execution_result'],
                                $task['orange_program_execution_result'],
                                $task['system_prompt'],
                                $task['default_summary'],
                                $task['default_self_check_questions']
                            );
                        }
                        }
                        echo 'success';
                    }
                }
                
                // Clean up temp directory
                array_map('unlink', glob("$temp_dir/*.*"));
                rmdir($temp_dir);
            } else {
                echo 'failed';
            }
            
        }       
        elseif($input === 'change-api-key') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $new_api_key = sanitize_text_field($_POST['new_api_key']);
            if ($user_manager->update_user_api_key($username, $new_api_key)){
                echo 'success';
            }
            else {
                echo 'error';
            }
            exit();
        }
        elseif($input === 'task-file') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }

            $result = uploadFile($_FILES['file']);
            echo json_encode($result);
        }
        elseif($input === 'python-data-file') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $result = uploadFile($_FILES['file']);
            echo json_encode($result);
        }
        elseif($input === 'orange-data-file') {
            if (!checkIfCurrentUserIsTeacher()){
                echo 'You are not authorized for the action';
                exit();
            }
            $result = uploadFile($_FILES['file']);
            echo json_encode($result);
        }
        elseif($input === 'done-task-file') {
            $result = uploadFile($_FILES['file']);
            $task_id = $_POST['task_id'];
            $class_id = $_POST['class_id'];
            $usingExternalInfo = $_POST['usingExternalInfo'];
            if ($usingExternalInfo == 'true') {
                $usingExternalInfo = true;

            }
            else {
                $usingExternalInfo = false;
            }
            $task = $taskManager->get_task($task_id, $username);
            $user_username = $username;
            $solution_file = $result[0];
            $solution_file_uri = $result[1];
            $taskManager->insert_student_task_solution($task_id, $class_id, $user_username, $solution_file, $solution_file_uri);
            $pdfReader = new GeminiManager($task_id, $class_id, $username);
            $correct_solution_uri = $task->clean_task_file_uri;
            if ($task->task_type == 'Excel') {
                $prompt = $prompts['done_excel_task_prompt'];
                if (!$pdfReader->fileExists($API_KEY, $task->clean_task_file_uri)) {
                    $filePath  = $task->task_file_correct;
                    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
                    $fileName = pathinfo($filePath, PATHINFO_FILENAME);
                    $excel_reader = new ExcelReader($filePath);
                    $excel_data = $excel_reader->readDataWithCoordinates();
                    // move excel_data to text file with the same name to the same path but the extension is .txt
                    $textFilePath = str_replace($fileExtension, 'txt', $filePath);
                    $correct_solution_uri = $pdfReader->uploadFileNew($API_KEY, $textFilePath, $fileName . '.txt', 'text/plain')[0];
                    $taskManager->update_task($current_task->task_id, $current_task->task_name, $current_task->task_text, $current_task->task_type, $current_task->task_file_clean, $current_task->task_file_correct, $current_task->task_file_uri, $correct_solution_uri, $current_task->system_prompt, $current_task->default_summary, $current_task->default_self_check_questions);
                }
                echo $pdfReader->analyzeExcel($API_KEY, $solution_file_uri, $correct_solution_uri, $prompt);
                return "Your uploaded solution: {$solution_file}";
            } elseif ($task->task_type == 'Python') {
                if (file_exists($solution_file)) {
                    $prompt = file_get_contents($solution_file);
                }
                else {
                    $prompt = "No solution file found";
                }
                echo $pdfReader->analyzePython($API_KEY, $task->python_data_file, $task->python_program_execution_result, $prompt);
                return "Your uploaded solution: {$solution_file}";
            } elseif ($task->task_type == 'Orange') {
                $solution_file_mime_type = $result[2];
                $prompt = $prompts['done_orange_task_prompt'];
                $pdfReader->analyzeOrange($API_KEY, $solution_file_uri, $solution_file_mime_type, $prompt);
                return "Your uploaded solution: {$solution_file}";
            }
        }
        elseif($input === 'current-task') {
            $task_id = $_GET['task_id'];
            if($task_id != null) {
                $current_task = $taskManager->get_task($task_id, $username);
                echo json_encode($current_task);
            }
            else {
                echo json_encode($lang["no_task_selected"]);
            }
        }
        elseif($input === 'current-class-id') {
            echo json_encode($class_id);
        }
        elseif($input === 'clean-chat-history') {
            global $current_task;
            $taskManager = new TaskManager();
            $studentChatHistory = $taskManager->get_student_task_chat_history($current_task->task_id, $current_task->class_id, $username);
            foreach ($studentChatHistory as $chat) {
                $taskManager->delete_student_task_chat_history($chat->id);
            }
            echo 'success';
        }
        else {
                $usingExternalInfo = $_POST['usingExternalInfo'];
                if ($usingExternalInfo == 'true') {
                    $usingExternalInfo = true;
                }
                else {
                    $usingExternalInfo = false;
                }

                if ($current_task->task_type == 'PDF') {
                    $pdfReader = new GeminiManager($current_task->task_id, $current_task->class_id, $username);
                    $fileUri = $current_task->task_file_uri;
                    if (!$pdfReader->fileExists($API_KEY, $fileUri)) {
                        $fileUri = $pdfReader->uploadFileNew($API_KEY, $current_task->task_file_clean, 'task111.pdf')[0];
                        $taskManager->update_task($current_task->task_id, $current_task->task_name, $current_task->task_text, $current_task->task_type, $current_task->task_file_clean, $current_task->task_file_correct, $fileUri, $current_task->system_prompt, $current_task->default_summary, $current_task->default_self_check_questions);
                    }
                    call_embedded_ocr_pdf( $prompts["ask_pdf_prompt1"] . $input . $prompts["ask_pdf_prompt2"], $fileUri);
                }
                elseif ($current_task->task_type == 'Excel') {
                    $pdfReader = new GeminiManager($current_task->task_id, $current_task->class_id, $username);
                    $taskManager = new TaskManager();
                    $student_solution = $taskManager->get_first_student_task_solution($current_task->task_id, $current_task->class_id, $username);
                    //check if student solution exists
                    $using_student_solution = false;
                    if ($student_solution) {
                        $using_student_solution = true;
                    }
                    $fileUri1 = $current_task->task_file_uri;
                    $fileUri2 = $current_task->clean_task_file_uri;
                    if($using_student_solution) {
                        $student_solutionFileUri = $student_solution->solution_file_uri;
                        $filePath  = $student_solution->solution_file;
                        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
                        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
                        $excel_reader = new ExcelReader($filePath);
                        $excel_data = $excel_reader->readDataWithCoordinates();
                        $textFilePath = str_replace($fileExtension, 'txt', $filePath);
                        $student_solutionFileUri = $pdfReader->uploadFileNew($API_KEY, $textFilePath, $fileName . '.txt', 'text/plain')[0];
                        $taskManager->update_student_task_solution($student_solution->solution_id, $current_task->task_id, $current_task->class_id, $student_solution->user_username, $student_solution->solution_file, $student_solutionFileUri);
                    }
                    else {
                        if (!$pdfReader->fileExists($API_KEY, $fileUri1)) {
                            $filePath  = $current_task->task_file_clean;
                            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
                            $fileName = pathinfo($filePath, PATHINFO_FILENAME);
                            $excel_reader = new ExcelReader($filePath);
                            $excel_data = $excel_reader->readDataWithCoordinates();
                            // move excel_data to text file with the same name to the same path but the extension is .txt
                            $textFilePath = str_replace($fileExtension, 'txt', $filePath);
                            $fileUri1 = $pdfReader->uploadFileNew($API_KEY, $textFilePath, $fileName . '.txt', 'text/plain')[0];
                            $taskManager->update_task($current_task->task_id, $current_task->task_name, $current_task->task_text, $current_task->task_type, $current_task->task_file_clean, $current_task->task_file_correct, $fileUri1, $current_task->clean_task_file_uri,$current_task->system_prompt, $current_task->default_summary, $current_task->default_self_check_questions);
                        }
                    }

                    if (!$pdfReader->fileExists($API_KEY, $fileUri2)) {
                        $filePath  = $current_task->task_file_correct;
                        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
                        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
                        $excel_reader = new ExcelReader($filePath);
                        $excel_data = $excel_reader->readDataWithCoordinates();
                        // move excel_data to text file with the same name to the same path but the extension is .txt
                        $textFilePath = str_replace($fileExtension, 'txt', $filePath);
                        $fileUri2 = $pdfReader->uploadFileNew($API_KEY, $textFilePath, $fileName . '.txt', 'text/plain')[0];
                        $taskManager->update_task($current_task->task_id, $current_task->task_name, $current_task->task_text, $current_task->task_type, $current_task->task_file_clean, $current_task->task_file_correct, $current_task->task_file_uri, $fileUri2, $current_task->system_prompt, $current_task->default_summary, $current_task->default_self_check_questions);
                    }

                    if ($using_student_solution) {
                        $fileUri1 = $student_solutionFileUri;
                    }

                    $response = $pdfReader->analyzeExcelQuestion($API_KEY, $fileUri1, $fileUri2, $input);
                    if ($usingExternalInfo) {
                        $response = $pdfReader->analyzeUrlEmbeddingsQuestion($API_KEY, '', '', $response, "Excel", false);
                    }
                    else {
                        echo $response;
                    }
                }
                elseif($current_task->task_type == 'Python') {
                    $pdfReader = new GeminiManager($current_task->task_id, $current_task->class_id, $username);
                    $response = $pdfReader->analyzePythonQuestion($API_KEY, $input);
                    if ($usingExternalInfo) {
                        $response = $pdfReader->analyzeUrlEmbeddingsQuestion($API_KEY, '', '', $response, "Python", false);
                    }
                    else {
                        echo $response;
                    }
                }
                elseif($current_task->task_type == 'Orange') {
                    $pdfReader = new GeminiManager($current_task->task_id, $current_task->class_id, $username);
                    $response = $pdfReader->analyzeOrangeQuestion($API_KEY, $input);
                    if ($usingExternalInfo) {
                        $response = $pdfReader->analyzeUrlEmbeddingsQuestion($API_KEY, $current_task->orange_data_file_uri, $current_task->orange_program_execution_result, $response, "Orange", false);
                    }
                    else {
                        echo $response;
                    }
                    return $response;
                }
        }
    } elseif (isset($_FILES['file'])) {
        $result = uploadFile($_FILES['file']);
        echo json_encode($result);
    } 
    exit(); // End the program after sending the response
}
?>


<!DOCTYPE html>
<html>
<head>
    <title><?php echo $lang['chatbot_title']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked@3.0.7/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.3/css/bulma.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/6.0.0/bootbox.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .float-right {
            float: right;
        }
        .card .btn.btn-link.collapsed {
            text-align: left;
        }
        .card .btn.btn-link {
            text-align: left;
        }
        .section {
            display: flex;
            padding: 0% !important;
        }
        .left-panel {
            width: 30%;
            height: 85vh;
            background-color: #f5f5f5;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .container {
            width: 70%;
            padding-left: 0.1rem !important;
            padding-right: 0px !important;
        }
        .top-left-buttons, .task-list, .settings-menu {
            padding: 0.2rem;
        }
        .top-left-buttons {
            padding: 1rem;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-start;
        }
        
        .top-left-buttons .button {
            flex: none;
            padding-left: 0.6em !important;
            padding-right: 0.6em !important;
            font-size: smaller;
        }
        .settings-menu {
            padding: 1rem;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-start;
        }
        
        .settings-menu .button {
            flex: none;
            padding-left: 0.6em !important;
            padding-right: 0.6em !important;
        }
        .task-list {
            flex-grow: 1;
            overflow-y: auto;
            height: 70%;
        }
        #class-list {
            flex-grow: 1;
            overflow-y: auto;
            height: 70%;
        }
        .top-left-buttons {
            height: 15%;
        }
        .bottom-left-buttons {
            height: 15%;
        }
        .task-item {
            padding: 0.3rem;
            cursor: pointer;
        }
        .task-item:hover {
            background-color: #e0e0e0;
        }
        .task-item.selected {
            background-color: #d3d3d3;
        }
        .class-list-item {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .class-list-item:hover {
            background-color: #f0f0f0;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .message-container {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 10px;
        }
        .message-container.user {
            justify-content: flex-end;
        }
        .message-bubble {
            max-width: 70%;
            padding: 10px;
            border-radius: 10px;
            background-color: #f1f1f1;
        }
        .message-bubble.user {
            background-color: #007bff;
            color: white;
        }
        .custom-file-label {
            width: 100%;
        }

        #chat-container {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
        }
        #loader {
            display: none;
            text-align: center;
        }
        #typing-indicator {
            display: none;
            font-style: italic;
            color: grey;
        }
        #openAddUsersModal {
            display: none;
        }
        .bootbox-input.bootbox-input-password.form-control {
            width: 90%;
        }
        .fa.fa-eye-slash.password-toggle {
            padding: 1.1em;
        }
        .fa.fa-eye.password-toggle {
            padding: 1.1em;
        }
        .form-switch {
            padding-left: 3em;
        }
        .message-bubble > * {
            padding-left: 0.5em;
        }
        #bottom-task-info {
            position: sticky;
            bottom: 0;
            background-color: #F9FBD3;
            padding: 10px;
            border-top: 1px solid #dee2e6;
            margin-top: auto;
            width: 100%;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
            text-align: left;
            transition: background-color 0.3s ease;
        }

        #bottom-task-info:hover {
            background-color:rgb(220, 255, 164);
        }

        .custom-file-label::after { content: "<?php echo $lang['upload']; ?>";}

        .remove-file-button {
            padding-top: 0.4em;
            display: none;
        }

        .remove-file-button {
            margin-top: 0.2rem;
        }

        .custom-file-label {
            overflow: hidden;
        }

    </style>
</head>
<body>
    <section class="section">
        <div class="left-panel">
            <div class="top-user-info" style="font-size: 0.8em; text-align: center; padding: 0.5em; display: flex; flex-direction: column;">
                <span><b><?php echo $lang['username']; ?>:</b> <?php echo $username; ?></span>
                <span><b><?php echo $lang['role']; ?>:</b> <?php echo ($current_user->user_role == 'teacher') ? $lang['teacher'] : $lang['student']; ?></span>
            </div>
            <div class="top-left-buttons">
                <button class="button is-link" id="openClassModalButton" onclick="openClassModal()">
                    <?php echo $lang['select_class']; ?> 
                     <span class="badge badge-light" id="classNotificationBadge" style="display: none;"> 0</span>
                </button>
                <button class="button is-primary teachers-buttons" style="display: none;" onclick="addTask()"><?php echo $lang['add_task']; ?></button>
            </div>
            <div class="task-list"></div>
            <a id="bottom-task-info" href="<?php echo home_url('/itaiassistant101/faq'); ?>">
                <?php echo $lang['faq']; ?>
            </a>
            <div class="bottom-left-buttons">
            <div class="settings-menu">
                <button class="button is-primary" onclick="openSettingsModal()"><?php echo $lang['settings']; ?></button>
                <button class="button is-danger" onclick="confirmLogout()"><?php echo $lang['logout']; ?></button>
            </div>
            </div>
        </div>
        <div class="container">
            <div id="chat-container" class="box"></div>
            <div id="typing-indicator"><?php echo $lang['assistant_typing']; ?></div>
            <div class="field is-grouped">
                <p class="control is-expanded">
                    <input id="user-input" class="input" type="text" placeholder="<?php echo $lang['please_enter_message']; ?>" disabled>
                </p>
                <p class="control">
                    <button id="sendMessageButton" class="button is-primary" onclick="sendMessage()" disabled><?php echo $lang['send']; ?></button>
                </p>
            </div>
            <p class="control">
                <div id="fileInputDiv" class="custom-file">
                    <input type="file" class="custom-file-input" id="fileInput" accept=".xls,.xlsx" onchange="uploadFile()" disabled>
                    <label class="custom-file-label" id="fileInputTaskDoneLabel" for="fileInput"><?php echo $lang['upload_excel_file']; ?></label>
                </div>
            </p>
            <div id="loader">
                <progress class="progress is-small is-primary" max="100"></progress>
            </div>
            <button class="button is-warning float-right" onclick="cleanHistory()" id="cleanHistory" disabled><?php echo $lang['clean_chat_history'] ?></button>
            <div class="form-check form-switch">
                <div id="useDocumentInformationDiv">
                    <input class="form-check-input" type="checkbox" role="switch" name="use_document_information" id="useDocumentInformation" disabled value="1" checked onchange="handleSwitchChange(this)">
                    <label class="form-check-label" for="useDocumentInformation">
                        <?php echo $lang['use_document_information']; ?>
                    </label>
                </div>
            </div>
            <br>
            <div class="text-center">
                <a href="login.php?lang=en" onclick="event.preventDefault(); window.location.href='<?php echo home_url('/itaiassistant101/login?lang=en'); ?>';"><?php echo $lang['lang_en'] ?></a>
                | <a href="login.php?lang=lt" onclick="event.preventDefault(); window.location.href='<?php echo home_url('/itaiassistant101/login?lang=lt'); ?>';"><?php echo $lang['lang_lt'] ?></a>
            </div>
        </div>
    </section>
    <!-- Add Task Modal -->
    <div class="modal" id="addTaskModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title"><?php echo $lang['add_task']; ?></p>
            <button class="delete" aria-label="close" onclick="closeModal()"></button>
        </header>
        <section class="modal-card-body">
            <div id="step1">
                <div class="field">
                    <label class="label"><?php echo $lang['task_name'] ?></label>
                    <div class="control">
                        <input class="input" type="text" id="taskName" placeholder="<?php echo $lang['enter_task_name']; ?>">
                    </div>
                </div>
                <div class="field">
                    <label class="label"><?php echo $lang['task_type']; ?></label>
                    <div class="control">
                        <div class="select">
                            <select id="taskType" onchange="toggleTaskTypeFields()">
                                <option value="PDF">PDF</option>
                                <option value="Excel">Excel</option>
                                <option value="Python">Python</option>
                                <option value="Orange">Orange</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field" id="taskFileField">
                    <label class="label"><?php echo $lang['upload_file']; ?></label>
                    <div class="control">
                        <input type="file" class="custom-file-input" id="taskFile" accept=".pdf,.xls,.xlsx" onchange="validateFileType()">
                        <label class="custom-file-label" for="taskFile"><?php echo $lang['upload_file_for_task']; ?></label>
                        <button class="button is-danger is-small remove-file-button" id="removeTaskFileButton" onclick="removeTaskFile('regular')"><?php echo $lang['remove_file']; ?></button>
                    </div>
                </div>
                <div class="excel-field">
                    <div class="field">
                        <label class="label"><?php echo $lang['upload_correct_exercise_file']; ?></label>
                        <div class="control">
                            <input type="file" class="custom-file-input" id="correctTaskFile" accept=".pdf,.xls,.xlsx" onchange="validateCorrectFileType()">
                            <label class="custom-file-label" for="correctTaskFile"><?php echo $lang['upload_correct_solution_file_for_task']; ?></label>
                            <button class="button is-danger is-small remove-file-button" id="removeCorrectTaskFileButton" onclick="removeTaskFile('correct-excel')"><?php echo $lang['remove_file']; ?></button>
                        </div>
                    </div>
                </div>
                <div class="python-field">
                    <div class="field">
                        <label class="label"><?php echo $lang['upload_python_data_file']; ?></label>
                        <div class="control">
                            <input type="file" class="custom-file-input" id="correctPythonDataFile" accept=".txt,.csv" onchange="validatePythonDataFileType()">
                            <label class="custom-file-label" for="correctPythonDataFile"><?php echo $lang['upload_python_data_file_for_checking']; ?></label>
                            <button class="button is-danger is-small remove-file-button" id="removePythonDataFileButton" onclick="removeTaskFile('data-python')"><?php echo $lang['remove_file']; ?></button>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label"><?php echo $lang['correct_program_execution_result']; ?></label>
                        <div class="control">
                            <textarea class="textarea" id="correctPythonProgramExecutionResult" placeholder="<?php echo $lang['enter_correct_program_execution_result']; ?>"></textarea>
                        </div>
                    </div>
                </div>
                <div class="orange-field">
                    <div class="field">
                        <label class="label"><?php echo $lang['upload_orange_data_file']; ?></label>
                        <div class="control">
                            <input type="file" class="custom-file-input" id="correctOrangeDataFile" accept=".tab,.tsv,.tab.gz,.tsv.gz,.gz,.tab.bz2,.tsv.bz2,.bz2,.tab.xz,.tsv.xz,.xz,.csv,.csv.gz,.csv.bz2,.basket,.bsk,.xls,.xlsx,.pkl,.pickle,.pkl.gz,.pickle.gz,.pkl.bz2,.pickle.bz2,.pkl.xz,.pickle.xz" onchange="validateOrangeDataFileType()">
                            <label class="custom-file-label" for="correctOrangeDataFile" title="<?php echo $lang['upload_orange_data_file_for_checking']; ?>"> 
                                <?php echo $lang['upload_orange_data_file_for_checking']; ?> 
                            </label>
                            <button class="button is-danger is-small remove-file-button" id="removeOrangeDataFileButton" onclick="removeTaskFile('data-orange')"><?php echo $lang['remove_file']; ?></button>                        
                        </div>
                    </div>
                    <div class="field">
                        <label class="label"><?php echo $lang['correct_program_execution_result']; ?></label>
                        <div class="control">
                            <textarea class="textarea" id="correctOrangeProgramExecutionResult" placeholder="<?php echo $lang['enter_correct_program_execution_result']; ?>"></textarea>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label"><?php echo $lang['task_description']; ?></label>
                    <div class="control">
                        <textarea class="textarea" id="taskDescription" placeholder="<?php echo $lang['enter_task_description']; ?>"></textarea>
                    </div>
                </div>
            </div>
            <div id="step2" style="display: none;">
                <p><strong><?php echo $lang['task_name']; ?>:</strong> <span id="displayTaskName"></span></p>
                <p><strong><?php echo $lang['task_type']; ?>:</strong> <span id="displayTaskType"></span></p>
                <p><strong><?php echo $lang['uploaded_file']; ?>:</strong> <span id="displayTaskFile"></span></p>
                <div class="excel-field">
                    <p><strong><?php echo $lang['uploaded_correct_file']; ?>:</strong> <span id="displayTaskCorrectFile"></span></p>
                </div> 
                <div class="python-field">
                    <p><strong><?php echo $lang['uploaded_python_data_file']; ?>:</strong> <span id="displayTaskPythonDataFile"></span></p>
                    <p><strong><?php echo $lang['correct_program_execution_result']; ?>:</strong> <span id="displayTaskPythonProgramExecutionResult"></span></p>
                </div>
                <div class="orange-field">
                    <p><strong><?php echo $lang['uploaded_orange_data_file']; ?>:</strong> <span id="displayTaskOrangeDataFile"></span></p>
                    <p><strong><?php echo $lang['correct_program_execution_result']; ?>:</strong> <span id="displayTaskOrangeProgramExecutionResult"></span></p>
                </div>
                <p><strong><?php echo $lang['task_description']; ?>:</strong> <span id="displayTaskDescription"></span></p>
            </div>
            <div id="step3" style="display: none;">
                <div class="pdf-field">
                    <div class="field">
                        <label class="label"><?php echo $lang['task_summary']; ?></label>
                        <button class="button" style="margin: 0.2rem;" onclick="writeTaskSummary()"><?php echo $lang['write_summary_with_ai']; ?></button>
                        <div class="control">
                            <textarea class="textarea" id="taskSummary" placeholder="Enter task summary"></textarea>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label"><?php echo $lang['task_self_check_questions']; ?></label>
                        <button class="button" style="margin: 0.2rem;" onclick="writeTaskQuestions()"><?php echo $lang['write_questions_with_ai']; ?></button>
                        <div class="control">
                            <textarea class="textarea" id="taskQuestions" style="display:none" placeholder="<?php echo $lang['enter_task_self_check_questions']; ?>"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div id="step4" style="display: none;">
                <p><strong><?php echo $lang['task_name']; ?>:</strong> <span id="finalTaskName"></span></p>
                <p><strong><?php echo $lang['task_type']; ?>:</strong> <span id="finalTaskType"></span></p>
                <p><strong><?php echo $lang['uploaded_file']; ?>:</strong> <span id="displayTaskFile2"></span></p>  
                <p><strong><?php echo $lang['task_description']; ?>:</strong> <span id="finalTaskDescription"></span></p>
                <p class="pdf-field"><strong><?php echo $lang['task_summary']; ?>:</strong> <span id="finalTaskSummary"></span></p>
                <p class="pdf-field"><strong><?php echo $lang['task_self_check_questions']; ?>:</strong> <span id="finalTaskQuestions"></span></p>
            </div>
        </section>
        <footer class="modal-card-foot">
            <button class="button" onclick="closeModal()"><?php echo $lang['cancel']; ?></button>
            <button class="button" id="prevButton" onclick="previousStep()"><?php echo $lang['back']; ?></button>
            <button class="button is-primary" id="nextButton" onclick="nextStep()"><?php echo $lang['next']; ?></button>
            <button class="button is-success" id="saveButton" onclick="saveTask()"><?php echo $lang['task_save']; ?></button>
        </footer>
        </div>
    </div>

        <!-- Add Class Modal -->
        <div class="modal" id="addClassModal">
            <div class="modal-background"></div>
            <div class="modal-card" style="width: 80%; max-width: 1200px;">
                <header class="modal-card-head">
                    <p class="modal-card-title"><?php echo $lang['select_class']; ?></p>
                    <button class="delete" aria-label="close" onclick="closeClassModal()"></button>
                </header>
                <section class="modal-card-body">
                    <div class="field">
                        <label class="label"><?php echo $lang['available_classes']; ?></label>
                        <div class="control">
                                <div class="table-container" style="width: 100%; min-width: 800px; overflow-x: auto;">
                                    <table class="table is-fullwidth">
                                        <thead>
                                            <tr>
                                                <th><?php echo $lang['class_name']?></th>
                                                <th><?php echo $lang['main_teacher']?></th>
                                                <th><?php echo $lang['creation_date']?></th>
                                                <th style="min-width: 200px;"><?php echo $lang['class_actions']?></th>
                                            </tr>
                                        </thead>
                                        <tbody id="class-list">
                                            <!-- Add rows here dynamically using PHP or JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                    </div>
                </section>
                <footer class="modal-card-foot">
                    <button class="button" onclick="closeClassModal()"><?php echo $lang['close']; ?></button>
                    <button class="button is-primary teachers-buttons" style="display: none;" onclick="addNewClass()"><?php echo $lang['add_new_class']; ?></button>
                </footer>
            </div>
        </div>

    <!-- User List Modal -->
    <div class="modal" id="userListModal">
        <div class="modal-background"></div>
        <div class="modal-card" style="width: -webkit-fill-available;">
            <header class="modal-card-head">
                <p class="modal-card-title"><?php echo $lang['class_user_list']; ?></p>
                <button class="delete" aria-label="close" onclick="closeUserListModal()"></button>
            </header>
            <section class="modal-card-body">
                <div class="field">
                    <label class="label"><?php echo $lang['class_user_list']; ?></label>
                    <div class="control">
                    <div class="table-container" style="width: 100%; min-width: 800px; overflow-x: auto;">
                        <table class="table is-fullwidth">
                            <thead>
                                <tr>
                                    <th><?php echo $lang['user_username']?></th>
                                    <th><?php echo $lang['user_name']?></th>
                                    <th><?php echo $lang['user_surname']?></th>
                                    <th><?php echo $lang['role']?></th>
                                    <?php if ($current_user->user_role === 'teacher') { ?>
                                    <th><?php echo $lang['temporary_password']?></th>
                                    <th style="min-width: 200px;"><?php echo $lang['user_actions']?></th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody id="class-user-list">
                                <!-- Add rows here dynamically using PHP or JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            <footer class="modal-card-foot">
                <button class="button is-primary teachers-buttons" style="display: none;" id="openAddUsersModal"><?php echo $lang['add_users']; ?></button>
                <button class="button" onclick="closeUserListModal()"><?php echo $lang['close']; ?></button>
                <button class="button is-secondary" onclick="backToClassList()"><?php echo $lang['back_to_class_list']; ?></button>
            </footer>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal" id="settingsModal">
        <div class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title"><?php echo $lang['settings']; ?></p>
                <button class="delete" aria-label="close" onclick="closeSettingsModal()"></button>
            </header>
            <section class="modal-card-body">
                <div class="columns">
                    <div class="column">
                        <!-- change api_key button -->
                        <div class="field teachers-buttons" style="display: none;">
                            <label class="label"><?php echo $lang['change_api_key']; ?></label>
                            <div class="control">
                                <button class="button is-primary" onclick="changeApiKey()"><?php echo $lang['change']; ?></button>
                            </div>
                        </div>

                        <!-- change password button -->
                        <div class="field">
                            <label class="label"><?php echo $lang['change_password']; ?></label>
                            <div class="control">
                                <button class="button is-primary" onclick="changePassword()"><?php echo $lang['change']; ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="column teachers-buttons" style="display: none;">
                        <!-- import tasks button -->
                        <div class="field">
                            <label class="label"><?php echo $lang['import_tasks'] ?></label>
                            <div class="control">
                                <button class="button is-primary" onclick="importTasks()"><?php echo $lang['import'] ?></button>
                            </div>
                        </div>

                        <!-- export tasks button -->
                        <div class="field">
                            <label class="label"><?php echo $lang['export_tasks'] ?></label>
                            <div class="control">
                                <button class="button is-primary" onclick="exportTasks()"><?php echo $lang['export'] ?></button>
                            </div>
                        </div>
                        </div>
                </div>


            </section>
            <footer class="modal-card-foot">
                <button class="button" onclick="closeSettingsModal()"><?php echo $lang['cancel']; ?></button>
            </footer>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal" id="loadingModal">
        <div class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title"><?php echo $lang['request_loading']; ?></p>
            </header>
            <section class="modal-card-body">
                <progress class="progress is-small is-primary" max="100"><?php echo $lang['loading']; ?></progress>
                <p><?php echo $lang['please_wait']; ?></p>
            </section>
        </div>
    </div>
    <script>
        const chatContainer = document.getElementById('chat-container');
        const userInput = document.getElementById('user-input');
        const loader = document.getElementById('loader');
        const typingIndicator = document.getElementById('typing-indicator');

        let currentTaskJson = '';
        let currentClassId = 0;

        let currentTaskFilePath = '';
        let currentTaskFileUri = '';

        let currentCorrectTaskFilePath = '';
        let currentCorrectTaskFileUri = '';

        let currentPythonDataFilePath = '';
        let currentPythonDataFileUri = '';

        let currentOrangeDataFilePath = '';
        let currentOrangeDataFileUri = '';
        let fetchedTasks = null;
        let tasksModalState = 'Add';
        let editedTaskID = 0;

        let taskFileChanged = false;
        let correctTaskFileChanged = false;
        let pythonDataFileChanged = false;
        let orangeDataFileChanged = false;

        let usingExternalInfo = true;

        let questionsGlobal = [];

        function displayMessage(text, sender) {
            const messageContainer = document.createElement('div');
            messageContainer.classList.add('message-container', sender);
            const messageBubble = document.createElement('div');
            messageBubble.classList.add('message-bubble', sender);

            // Convert URLs in the text to clickable links
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            const html = marked(text.replace(urlRegex, function(url) {
                const domain = url.replace(/^(?:https?:\/\/)?(?:www\.)?([^\/]+).*$/, '$1');
                return `<a href="${url}" target="_blank" class="btn btn-secondary  btn-sm">${domain}</a><br>`;
            }));

            messageBubble.innerHTML = html;
            messageContainer.appendChild(messageBubble);
            chatContainer.appendChild(messageContainer);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function showLoader() {
            loader.style.display = 'block';
        }

        function hideLoader() {
            loader.style.display = 'none';
        }

        function showTypingIndicator() {
            typingIndicator.style.display = 'block';
        }

        function hideTypingIndicator() {
            typingIndicator.style.display = 'none';
        }

        function sendMessage() {
            const message = userInput.value;
            // clear the input field
            userInput.value = '';
            displayMessage(message, 'user');
            showLoader();
            showTypingIndicator();
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=' + encodeURIComponent(message) + '&usingExternalInfo=' + usingExternalInfo,
            })
            .then(response => response.text())
            .then(text => {
                displayMessage(text, 'model');
                hideLoader();
                hideTypingIndicator();
            })
            .catch(error => {
                console.error('An error occurred:', error);
                hideLoader();
                hideTypingIndicator();
            });
        }

        function sendIntroMessage() {
            showLoader();
            showTypingIndicator();
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=intro-message',
            })
            .then(response => response.text())
            .then(text => {
                displayMessage(text, 'model');
                hideLoader();
                hideTypingIndicator();
            })
            .catch(error => {
                console.error('An error occurred:', error);
                hideLoader();
                hideTypingIndicator();
            });
        }

        function showLoadingModal() {
            document.getElementById('loadingModal').classList.add('is-active');
        }

        function hideLoadingModal() {
            document.getElementById('loadingModal').classList.remove('is-active');
        }

        function writeTaskSummary() {
            showLoadingModal();
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=task-summary&fileUri=' + encodeURIComponent(currentTaskFileUri),
            })
            .then(response => response.text())
            .then(text => {
                document.getElementById('taskSummary').value = text;
                hideLoadingModal();
            })
            .catch(error => {
                console.error('An error occurred:', error);
                hideLoadingModal();
            });
        }

        function writeTaskQuestions() {
            showLoadingModal();
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=task-questions&fileUri=' + encodeURIComponent(currentTaskFileUri),
            })
            .then(response => response.text())
            .then(text => {
                document.getElementById('taskQuestions').value = text;
                questionsGlobal = questionsGlobal.concat(JSON.parse(text).questions);
                // const questions = JSON.parse(text).questions;
                const taskQuestionsElement = document.getElementById('taskQuestions');
                createSelfCheckAccordion(questionsGlobal, taskQuestionsElement);
                hideLoadingModal();
            })
            .catch(error => {
                console.error('An error occurred:', error);
                hideLoadingModal();
            });
        }

        function uploadFile() {
            var fileInput = document.getElementById('fileInput');
            var file = fileInput.files[0];

            if (file) {
                var fileName = file.name;
                var fileExtension = fileName.split('.').pop().toLowerCase();

                if(currentTaskJson.task_type === 'Excel') {
                    if (fileExtension === 'xls' || fileExtension === 'xlsx') {
                        // Create form data and append file
                        var formData = new FormData();
                        formData.append('file', file);
                        formData.append('message', 'done-task-file');
                        formData.append('task_id', currentTaskJson.task_id);
                        formData.append('class_id', currentTaskJson.class_id);
                        formData.append('usingExternalInfo', usingExternalInfo);


                        displayMessage('<?php echo $lang['submitted_file']; ?>: ' + fileName, 'user');
                        showLoader();
                        showTypingIndicator();
                        // Use Fetch or AJAX to send file to server
                        fetch('index.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(result => {
                            displayMessage(result, 'model');
                            hideLoader();
                            hideTypingIndicator();
                            bootbox.alert("<?php echo $lang['file_uploaded_successfully']; ?>");
                        })
                        .catch(error => {
                            console.error('<?php echo $lang['error']; ?>:', error);
                            bootbox.alert("<?php echo $lang['file_upload_failed']; ?>");
                            document.getElementById('fileInput').value = ''; // Clear the input field
                        });
                    } else {
                        bootbox.alert("<?php echo $lang['upload_xls_xlsx_file']; ?>");
                        document.getElementById('fileInput').value = ''; // Clear the input field
                    }
                } else if (currentTaskJson.task_type === 'Python') {
                    if (fileExtension === 'py' || fileExtension === 'txt') {
                        // Create form data and append file
                        var formData = new FormData();
                        formData.append('file', file);
                        formData.append('message', 'done-task-file');
                        formData.append('task_id', currentTaskJson.task_id);
                        formData.append('class_id', currentTaskJson.class_id);


                        displayMessage('<?php echo $lang['submitted_file']; ?>: ' + fileName, 'user');
                        showLoader();
                        showTypingIndicator();
                        // Use Fetch or AJAX to send file to server
                        fetch('index.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(result => {
                            displayMessage(result, 'model');
                            hideLoader();
                            hideTypingIndicator();
                            bootbox.alert("<?php echo $lang['file_uploaded_successfully']; ?>");
                        })
                        .catch(error => {
                            console.error('<?php echo $lang['error']; ?>:', error);
                            bootbox.alert("<?php echo $lang['file_upload_failed']; ?>");
                            document.getElementById('fileInput').value = ''; // Clear the input field
                        });
                    } else {
                        bootbox.alert("<?php echo $lang['upload_py_txt_file']; ?>");
                        document.getElementById('fileInput').value = ''; // Clear the input field
                    }
                } else if (currentTaskJson.task_type === 'Orange') {
                    if (fileExtension === 'png' || fileExtension === 'jpg' || fileExtension === 'jpeg') {
                        // Create form data and append file
                        var formData = new FormData();
                        formData.append('file', file);
                        formData.append('message', 'done-task-file');
                        formData.append('task_id', currentTaskJson.task_id);
                        formData.append('class_id', currentTaskJson.class_id);


                        displayMessage('<?php echo $lang['submitted_file']; ?>: ' + fileName, 'user');
                        showLoader();
                        showTypingIndicator();
                        // Use Fetch or AJAX to send file to server
                        fetch('index.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(result => {
                            displayMessage(result, 'model');
                            hideLoader();
                            hideTypingIndicator();
                            bootbox.alert("<?php echo $lang['file_uploaded_successfully']; ?>");
                        })
                        .catch(error => {
                            console.error('<?php echo $lang['error']; ?>:', error);
                            bootbox.alert("<?php echo $lang['file_upload_failed']; ?>");
                            document.getElementById('fileInput').value = ''; // Clear the input field
                        });
                    } else {
                        bootbox.alert("<?php echo $lang['upload_png_jpg_jpeg_file']; ?>");
                        document.getElementById('fileInput').value = ''; // Clear the input field
                    }
                } 

                
            } else {
                bootbox.alert("<?php echo $lang['no_file_selected']; ?>");
            }
            document.getElementById('fileInput').value = ''; // Clear the input field
            toggleLoadTaskTypeFields();
        }

        function validateFileType() {
            const taskType = document.getElementById('taskType').value;
            const fileInput = document.getElementById('taskFile');
            const file = fileInput.files[0];
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if ((taskType === 'PDF' && fileExtension !== 'pdf') || 
                (taskType === 'Excel' && (fileExtension !== 'xls' && fileExtension !== 'xlsx'))) {
                bootbox.alert('<?php echo $lang['please_upload_valid']; ?> ' + taskType + ' <?php echo $lang['file']; ?>.');
                fileInput.value = ''; // Clear the input
                return;
            }
            showLoadingModal();
            // Create form data and append file
            var formData = new FormData();
            formData.append('file', file);
            formData.append('message', 'task-file');
            
            // Use Fetch or AJAX to send file to server
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                result = JSON.parse(result);
                currentTaskFilePath = result[0].replace(/(\r\n|\n|\r)/gm, "");
                currentTaskFileUri = result[1].replace(/(\r\n|\n|\r)/gm, "");
                const displayFileName = currentTaskFilePath.split('/').pop();
                document.getElementById('taskFile').nextElementSibling.innerText = displayFileName;
                hideLoadingModal();
                bootbox.alert("<?php echo $lang['file_uploaded_successfully']; ?>");

            })
            .catch(error => {
                console.error('Error:', error);
                hideLoadingModal();
                bootbox.alert('<?php echo $lang['file_upload_failed']; ?>');
                document.getElementById('taskFile').value = ''; // Clear the input field
                document.getElementById('taskFile').nextElementSibling.innerText = '<?php echo $lang['upload_file_for_task']; ?>';
            });
        }

        //update chat ui based on current task task_type
        function updateChatUI() {
            if (currentTaskJson.task_type === 'PDF') {
                //hide file upload near chat message send button
                document.getElementById('fileInputDiv').style.display = 'none';
                try {
                    questions = JSON.parse(currentTaskJson.default_self_check_questions).questions; //.replace(/\\\"/g, '"')).questions;
                    waitForMessageBubblesToAddSelfCheckQuestions(questions);
                } catch (e) {
                    console.error('Failed to parse JSON or no self check questions in task:', e);
                } 
            }
            else {
                <?php loadChatHistory(); ?>
            }
        }

        function waitForMessageBubblesToAddSelfCheckQuestions(questions) {
            const interval = setInterval(() => {
                const messageBubbles = document.querySelectorAll('.message-bubble.model');
                if (messageBubbles.length > 0) {
                    clearInterval(interval);
                    const elementToAddAfter = messageBubbles[messageBubbles.length - 1].children[messageBubbles[messageBubbles.length - 1].children.length - 1];
                    createSelfCheckAccordion(questions, elementToAddAfter, true);
                    <?php loadChatHistory(); ?>
                }
            }, 100); // Check every 0.1 seconds
        }

        function validateCorrectFileType() {
            const taskType = document.getElementById('taskType').value;
            const fileInput = document.getElementById('correctTaskFile');
            const file = fileInput.files[0];
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if ((taskType === 'Excel' && (fileExtension !== 'xls' && fileExtension !== 'xlsx'))) {
                bootbox.alert('<?php echo $lang['please_upload_valid']; ?> ' + taskType + ' <?php echo $lang['file']; ?>.');
                fileInput.value = ''; // Clear the input
                return;
            }

            showLoadingModal();
            // Create form data and append file
            var formData = new FormData();
            formData.append('file', file);
            formData.append('message', 'task-file');
            
            // Use Fetch or AJAX to send file to server
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                result = JSON.parse(result);
                currentCorrectTaskFilePath = result[0].replace(/(\r\n|\n|\r)/gm, "");
                currentCorrectTaskFileUri = result[1].replace(/(\r\n|\n|\r)/gm, "");
                const displayFileName = currentCorrectTaskFilePath.split('/').pop();
                document.getElementById('correctTaskFile').nextElementSibling.innerText = displayFileName;
                hideLoadingModal();
                bootbox.alert("<?php echo $lang['file_uploaded_successfully']; ?>");

            })
            .catch(error => {
                console.error('Error:', error);
                hideLoadingModal();
                bootbox.alert('<?php echo $lang['file_upload_failed']; ?>');
                document.getElementById('correctTaskFile').value = ''; // Clear the input field
                document.getElementById('correctTaskFile').nextElementSibling.innerText = '<?php echo $lang['upload_correct_solution_file_for_task']; ?>';

            });
        }
        

        function validatePythonDataFileType() {
            const taskType = document.getElementById('fileInput').value;
            const fileInput = document.getElementById('correctPythonDataFile');
            const file = fileInput.files[0];
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if ((taskType === 'Python' && (fileExtension !== 'txt' && fileExtension !== 'csv'))) {
                bootbox.alert('<?php echo $lang['please_upload_valid']; ?> (.txt,.csv) <?php echo $lang['file']; ?>.');
                fileInput.value = ''; // Clear the input
                return;
            }

            showLoadingModal();
            // Create form data and append file
            var formData = new FormData();
            formData.append('file', file);
            formData.append('message', 'python-data-file');
            
            // Use Fetch or AJAX to send file to server
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                result = JSON.parse(result);
                currentPythonDataFilePath = result[0].replace(/(\r\n|\n|\r)/gm, "");
                currentPythonDataFileUri = result[1].replace(/(\r\n|\n|\r)/gm, "");
                const displayFileName = currentPythonDataFilePath.split('/').pop();
                document.getElementById('correctPythonDataFile').nextElementSibling.innerText = displayFileName;

                hideLoadingModal();
                bootbox.alert("<?php echo $lang['file_uploaded_successfully']; ?>");

            })
            .catch(error => {
                console.error('Error:', error);
                hideLoadingModal();
                bootbox.alert('<?php echo $lang['file_upload_failed']; ?>');
                document.getElementById('correctPythonDataFile').value = ''; // Clear the input field
                document.getElementById('correctPythonDataFile').nextElementSibling.innerText = '<?php echo $lang['upload_python_data_file_for_checking']; ?>';

            });
        }

        function validateOrangeDataFileType() {
            const taskType = document.getElementById('fileInput').value;
            const fileInput = document.getElementById('correctOrangeDataFile');
            const file = fileInput.files[0];
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if ((taskType === 'Orange' && !['tab', 'tsv', 'tab.gz', 'tsv.gz', 'gz', 'tab.bz2', 'tsv.bz2', 'bz2', 'tab.xz', 'tsv.xz', 'xz', 'csv', 'csv.gz', 'csv.bz2', 'basket', 'bsk', 'xls', 'xlsx', 'pkl', 'pickle', 'pkl.gz', 'pickle.gz', 'pkl.bz2', 'pickle.bz2', 'pkl.xz', 'pickle.xz'].includes(fileExtension))) {
                bootbox.alert('<?php echo $lang['please_upload_valid']; ?> (.tab, .tsv, .tab.gz, .tsv.gz, .gz, .tab.bz2, .tsv.bz2, .bz2, .tab.xz, .tsv.xz, .xz, .csv, .csv.gz, .csv.bz2, .basket, .bsk, .xls, .xlsx, .pkl, .pickle, .pkl.gz, .pickle.gz, .pkl.bz2, .pickle.bz2, .pkl.xz, .pickle.xz) <?php echo $lang['file']; ?>.');
                fileInput.value = ''; // Clear the input
                return;
            }

            showLoadingModal();
            // Create form data and append file
            var formData = new FormData();
            formData.append('file', file);
            formData.append('message', 'orange-data-file');
            
            // Use Fetch or AJAX to send file to server
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                result = JSON.parse(result);
                currentOrangeDataFilePath = result[0].replace(/(\r\n|\n|\r)/gm, "");
                currentOrangeDataFileUri = result[1].replace(/(\r\n|\n|\r)/gm, "");
                const displayFileName = currentOrangeDataFilePath.split('/').pop();
                document.getElementById('correctOrangeDataFile').nextElementSibling.innerText = displayFileName;
                hideLoadingModal();
                bootbox.alert("<?php echo $lang['file_uploaded_successfully']; ?>");

            })
            .catch(error => {
                console.error('Error:', error);
                hideLoadingModal();
                bootbox.alert('<?php echo $lang['file_upload_failed']; ?>');
                document.getElementById('correctOrangeDataFile').value = ''; // Clear the input field
                document.getElementById('correctOrangeDataFile').nextElementSibling.innerText = '<?php echo $lang['upload_orange_data_file_for_checking']; ?>';

            });
        }

        //function to get the current task json calling current-task AJAX
        function getCurrentTask() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=current-task',
            })
            .then(response => response.json())
            .then(task => {
                currentTaskJson = task;
                updateChatUI();
                highlightCurrentTask();
                if (typeof currentTaskJson.task_type !== 'undefined' && currentTaskJson.task_type != '') {
                    enableChatInputs();
                    toggleLoadTaskTypeFields();
                }
            })
            .catch(error => {
                console.error('An error occurred:', error);
            });
        }

        // current-class-id
        function getCurrentClassId() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=current-class-id',
            })
            .then(response => response.json())
            .then(classId => {
                getTasksList(classId);
                getClassList();
                currentClassId = classId;
            })
            .catch(error => {
                console.error('An error occurred:', error);
            });
        }

        function cleanHistory() {
            bootbox.confirm({
                title: "<?php echo $lang['confirm_clean_chat_history']; ?>",
                message: "<?php echo $lang['confirm_clean_chat_history']; ?>",
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (result) {
                    if(result) {
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'message=clean-chat-history',
                        })
                        .then(response => response.text())
                        .then(result => {
                            updateChatUI();
                            chatContainer.innerHTML = '';
                            bootbox.alert("<?php echo $lang['chat_history_cleaned']; ?>");
                            sendIntroMessage();

                        })
                        .catch(error => {
                            console.error('An error occurred:', error);
                        });
                    }
                }
            });
        }

        function enableChatInputs() {
            userInput.disabled = false;
            document.getElementById('fileInput').disabled = false;
            document.getElementById('sendMessageButton').disabled = false;
            document.getElementById('cleanHistory').disabled = false;
            document.getElementById('useDocumentInformation').disabled = false;
            toggleTaskTypeFields();
        }

        function highlightCurrentTask() {
            const interval = setInterval(() => {
                const taskElement = document.getElementById('task-' + currentTaskJson.task_id);
                if (taskElement) {
                    taskElement.style.backgroundColor = '#d3d3d3'; // Highlight color
                    taskElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    clearInterval(interval);
                }
            }, 100); // Check every 100 milliseconds
        }

        function getTasksList(classId) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=task-list&class_id=' + classId,
            })
            .then(response => response.json())
            .then(tasks => {
                fetchedTasks = tasks;
                const taskList = document.querySelector('.task-list');
                // taskList.innerHTML = '';
                tasks.forEach(task => {
                    const newTaskItem = document.createElement('div');
                    newTaskItem.classList.add('task-item');
                    newTaskItem.id = 'task-' + task.task_id;
                    
                    const icon = document.createElement('img');
                    icon.style.width = '1.25em';
                    icon.style.height = '1.25em';
                    icon.style.marginRight = '0.625em';
                    if (task.task_type === 'PDF') {
                        icon.src = "<?php echo plugin_dir_url(__FILE__) . 'icons/pdf.png'; ?>";
                    } else if (task.task_type === 'Excel') {
                        icon.src = "<?php echo plugin_dir_url(__FILE__) . 'icons/excel.png'; ?>";
                    } else if (task.task_type === 'Python') {
                        icon.src = "<?php echo plugin_dir_url(__FILE__) . 'icons/python.png'; ?>";
                    } else if (task.task_type === 'Orange') {
                        icon.src = "<?php echo plugin_dir_url(__FILE__) . 'icons/orange.png'; ?>";
                    }
                    
                    const textContent = document.createElement('span');
                    textContent.textContent = "#" + task.task_id + " " + task.task_name;
                    
                    newTaskItem.appendChild(icon);
                    newTaskItem.appendChild(textContent);
                    // Create three-dot menu
                    <?php if ($current_user->user_role === 'teacher') { ?>
                        const menuWrapper = document.createElement('div');
                        menuWrapper.classList.add('teachers-buttons');
                        menuWrapper.style.float = 'right';
                        menuWrapper.style.marginRight = '0.5em';
                        menuWrapper.style.position = 'relative';
                        menuWrapper.style.clear = 'right';

                        const threeDots = document.createElement('i');
                        threeDots.classList.add('fas', 'fa-ellipsis-v');
                        threeDots.style.cursor = 'pointer';
                        threeDots.style.fontSize = '1.2em';
                        threeDots.addEventListener('mouseover', function() {
                            threeDots.style.color = 'gray';
                        });
                        threeDots.addEventListener('mouseout', function() {
                            threeDots.style.color = '';
                        });

                        const dropdownMenu = document.createElement('div');
                        dropdownMenu.style.display = 'none';
                        dropdownMenu.style.position = 'absolute';
                        dropdownMenu.style.right = '0';
                        dropdownMenu.style.backgroundColor = '#fff';
                        dropdownMenu.style.border = '1px solid #ccc';
                        dropdownMenu.style.padding = '0.5em';
                        dropdownMenu.style.minWidth = '150px';

                        const editOption = document.createElement('div');
                        editOption.textContent = 'Edit';
                        editOption.style.cursor = 'pointer';
                        editOption.addEventListener('mouseover', function() {
                            editOption.style.backgroundColor = '#eee';
                        });
                        editOption.addEventListener('mouseout', function() {
                            editOption.style.backgroundColor = '';
                        });
                        editOption.onclick = function(e) {
                            e.stopPropagation();
                            editTask(task.task_id);
                            dropdownMenu.style.display = 'none';
                        };
                        dropdownMenu.appendChild(editOption);

                        const deleteOption = document.createElement('div');
                        deleteOption.textContent = 'Delete';
                        deleteOption.style.cursor = 'pointer';
                        deleteOption.addEventListener('mouseover', function() {
                            deleteOption.style.backgroundColor = '#eee';
                        });
                        deleteOption.addEventListener('mouseout', function() {
                            deleteOption.style.backgroundColor = '';
                        });
                        deleteOption.onclick = function(e) {
                            e.stopPropagation();
                            deleteTask(task.task_id);
                            dropdownMenu.style.display = 'none';
                        };
                        dropdownMenu.appendChild(deleteOption);

                        threeDots.onclick = function(e) {
                            e.stopPropagation();
                            dropdownMenu.style.display = (dropdownMenu.style.display === 'none') ? 'block' : 'none';
                        };

                        document.addEventListener('click', function(evt) {
                            if (!menuWrapper.contains(evt.target)) {
                                dropdownMenu.style.display = 'none';
                            }
                        });
                        threeDots.onclick = function(e) {
                            e.stopPropagation();
                            bootbox.dialog({
                                title: `<?php echo $lang['task_']?>#${task.task_id}<?php echo $lang['_information']; ?>`,
                                message: `<p><?php echo $lang['name_']?>${task.task_name}</p><p><?php echo $lang['type_']; ?>${task.task_type}</p>`,
                                size: 'large',
                                buttons: {
                                    cancel: {
                                        label: '<?php echo $lang['cancel']; ?>',
                                        className: 'btn-secondary'
                                    },
                                    edit: {
                                        label: '<?php echo $lang['edit']; ?>',
                                        className: 'btn-primary teachers-buttons',
                                        callback: function() {
                                            editTask(task.task_id);
                                        }
                                    },
                                    delete: {
                                        label: '<?php echo $lang['delete']; ?>',
                                        className: 'btn-danger teachers-buttons',
                                        callback: function() {
                                            deleteTask(task.task_id);
                                        }
                                    }
                                }
                            });
                        };

                        menuWrapper.appendChild(threeDots);
                        menuWrapper.appendChild(dropdownMenu);
                        newTaskItem.appendChild(menuWrapper);
                    <?php } ?> 
                    newTaskItem.onclick = function() {
                        reloadWithTaskId(task.task_id);
                    };
                    taskList.appendChild(newTaskItem);
                });
            })
            .catch(error => {
                console.error('An error occurred:', error);
            });
        }


        function getClassList() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=class-list',
            })
            .then(response => response.json())
            .then(classes => {
                const classList = document.querySelector('#class-list');
                classList.innerHTML = '';
                classes.forEach(classItem => {
                    const newClassItem = document.createElement('tr');

                    // Make row text bold if it's the last used class
                    if (classItem.is_last_used) {
                        newClassItem.style.fontWeight = 'bold';
                    }

                    newClassItem.classList.add('class-list-item');

                    const classNameElement = document.createElement('td');
                    
                    classNameElement.textContent = classItem.class_name  + " ";
                    if (classItem.is_default) {
                        const defaultBadge = document.createElement('span');
                        defaultBadge.classList.add('badge', 'badge-info');
                        defaultBadge.id = 'default-class-badge';
                        defaultBadge.style.display = 'none';
                        defaultBadge.textContent = '0';
                        classNameElement.appendChild(defaultBadge);
                    }
                    newClassItem.appendChild(classNameElement);

                    const classMainTeacherElement = document.createElement('td');
                    classMainTeacherElement.textContent = classItem.class_main_teacher;
                    newClassItem.appendChild(classMainTeacherElement);

                    const classCreationDateElement = document.createElement('td');
                    classCreationDateElement.textContent = classItem.class_creation_date;
                    newClassItem.appendChild(classCreationDateElement);

                    newClassItem.onclick = function(e) {
                        if (e.target.tagName !== 'BUTTON') {
                            selectClass(classItem.class_id);
                        }
                    };
                    
                    const classActionsElement = document.createElement('td');

                    // add table cell for actions
                    if (classItem.use_actions) {
                        classActionsElement.style.display = 'flex';
                        classActionsElement.style.gap = '4px';

                        if (!classItem.is_default) {
                            const deleteButton = document.createElement('button');
                            deleteButton.textContent = '<?php echo $lang['delete']; ?>';
                            deleteButton.classList.add('button', 'is-danger', 'is-small');
                            deleteButton.onclick = function() {
                                deleteClass(classItem.class_id, classItem.class_name);
                            };
                            classActionsElement.appendChild(deleteButton);
                        }
                        
                        const editButton = document.createElement('button');
                        editButton.textContent = '<?php echo $lang['edit']; ?>';
                        editButton.classList.add('button', 'is-warning', 'is-small');
                        editButton.onclick = function() {
                            editClass(classItem.class_id, classItem.class_name);
                        };
                        classActionsElement.appendChild(editButton);
                    }
                    // Only show select button if not last used
                    if (!classItem.is_last_used) {
                        const selectButton = document.createElement('button');
                        selectButton.textContent = '<?php echo $lang['select']; ?>';
                        selectButton.classList.add('button', 'is-primary', 'is-small');
                        selectButton.onclick = function() {
                            selectClassWithReload(classItem.class_id);
                        };
                        classActionsElement.appendChild(selectButton);
                    }

                    newClassItem.appendChild(classActionsElement);
                    classList.appendChild(newClassItem);
                });
                getTiedRequestsCount();
            })
            .catch(error => {
                console.error('An error occurred:', error);
            });
        }

        function getClassUserList(classId) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=class-user-list&class_id=' + classId,
            })
            .then(response => response.json())
            .then(users => {
                const classUserList = document.querySelector('#class-user-list');
                classUserList.innerHTML = '';
                users.forEach(userItem => {
                    const newUserItem = document.createElement('tr');
                    // Make row text bold if it's the last used class
                    if (userItem.is_current_user) {
                        newUserItem.style.fontWeight = 'bold';
                    }
                    const userUserNameElement = document.createElement('td');
                    userUserNameElement.textContent = userItem.user_username;
                    newUserItem.appendChild(userUserNameElement);

                    const userNameElement = document.createElement('td');
                    userNameElement.textContent = userItem.user_name;
                    newUserItem.appendChild(userNameElement);

                    const userSurameElement = document.createElement('td');
                    userSurameElement.textContent = userItem.user_surname;
                    newUserItem.appendChild(userSurameElement);

                    const userRoleElement = document.createElement('td');
                    if (userItem.user_role === 'teacher') {
                        userRoleElement.textContent = '<?php echo $lang['teacher']; ?>';
                    } else {
                        userRoleElement.textContent = '<?php echo $lang['student']; ?>';
                    }
                    newUserItem.appendChild(userRoleElement);

                    const temporaryPasswordElement = document.createElement('td');
                    temporaryPasswordElement.textContent = userItem.decoded_temporary_password;
                    newUserItem.appendChild(temporaryPasswordElement);

                    // add table cell for actions
                    const classActionsElement = document.createElement('td');
                    if (!userItem.is_teacher && !userItem.is_current_user) {
                        const deleteButton = document.createElement('button');
                        deleteButton.textContent = '<?php echo $lang['delete']; ?>';
                        deleteButton.classList.add('button', 'is-danger');
                        deleteButton.style.margin = '1px';
                        deleteButton.onclick = function() {
                            deleteUserFromClass(userItem.user_username, classId);
                        };
                        classActionsElement.appendChild(deleteButton);

                        if (userItem.decoded_temporary_password === '') {
                            const resetPasswordButton = document.createElement('button');
                            resetPasswordButton.textContent = '<?php echo $lang['reset_password']; ?>';
                            resetPasswordButton.classList.add('button', 'is-warning');
                            resetPasswordButton.style.margin = '1px';
                            resetPasswordButton.onclick = function() {
                                resetUserPassword(userItem.user_username, classId);
                            };
                            classActionsElement.appendChild(resetPasswordButton);
                        }
                    }

                    if (userItem.user_role === 'student' && userItem.tied_request !== '' && userItem.tied_request !== null) {
                        const acceptToClassButton = document.createElement('button');
                        acceptToClassButton.textContent = '<?php echo $lang['accept_to_class']; ?>';
                        acceptToClassButton.classList.add('button', 'is-success');
                        acceptToClassButton.style.margin = '1px';
                        acceptToClassButton.id = 'acceptToClassButton-' + userItem.user_username;
                        acceptToClassButton.onclick = function() {
                            acceptUserToClass(userItem.user_username, classId);
                        };
                        classActionsElement.appendChild(acceptToClassButton);
                    }
                    newUserItem.id = 'user-' + userItem.user_username;
                    newUserItem.appendChild(classActionsElement);

                    classUserList.appendChild(newUserItem);
                });
            })
            .catch(error => {
                console.error('An error occurred:', error);
            });
        }

        function checkIfClassIsNotMainForTeacher(classId) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=check-if-class-is-not-main-for-teacher&class_id=' + classId,
            }).then(response => response.text())
            .then(result => {
                if (result.includes('true')) {
                    document.getElementById('openAddUsersModal').style.display = 'block';
                    document.getElementById('openAddUsersModal').onclick = function() { 
                        openAddUsersModal(classId);
                    };
                    openUserListModal();
                } else {
                    document.getElementById('openAddUsersModal').style.display = 'none';
                }
                openUserListModal();

            })
        }

        function deleteUserFromClass(userId, classId) {
            bootbox.confirm({
                title: "<?php echo $lang['confirm_delete_user_from_class']; ?>",
                message: "<?php echo $lang['confirm_delete_user_from_class']; ?>",
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (result) {
                    if(result) {
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'message=delete-user-from-class&user_id=' + userId + '&class_id=' + classId,
                        })
                        .then(response => response.text())
                        .then(result => {
                            document.getElementById('user-' + userId).remove();
                            bootbox.alert("<?php echo $lang['user_deleted_from_class']; ?>");
                            getClassList();
                        })
                        .catch(error => {
                            bootbox.alert("<?php echo $lang['error_deleting_user_from_class']; ?>");
                        });
                    }
                }
            });
        }

        function resetUserPassword(userId, classId) {
            bootbox.confirm({
                title: "<?php echo $lang['confirm_reset_user_password']; ?>",
                message: "<?php echo $lang['confirm_reset_user_password']; ?>",
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (result) {
                    if(result) {
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'message=reset-user-password&user_id=' + userId + '&classId=' + classId,
                        })
                        .then(response => response.text())
                        .then(result => {
                            if (result.includes('success')) {
                                bootbox.alert("<?php echo $lang['user_password_reset']; ?>");
                                getClassUserList(classId);
                            } else {
                                bootbox.alert("<?php echo $lang['error_resetting_user_password']; ?>");
                            }
                        })
                        .catch(error => {
                            bootbox.alert("<?php echo $lang['error_resetting_user_password']; ?>");
                        });
                    }
                }
            });
        }

        function acceptUserToClass(userId, classId) {
            bootbox.confirm({
                title: "<?php echo $lang['confirm_accept_user_to_class']; ?>",
                message: "<?php echo $lang['confirm_accept_user_to_class']; ?>",
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (result) {
                    if(result) {
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'message=accept-user-to-class&user_id=' + userId + '&class_id=' + classId,
                        })
                        .then(response => response.text())
                        .then(result => {
                            document.getElementById('acceptToClassButton-' + userId).remove();
                            bootbox.alert("<?php echo $lang['user_accepted_to_class_successfully']; ?>");
                            getClassList();
                        })
                        .catch(error => {
                            bootbox.alert("<?php echo $lang['error_accepting_user_to_class']; ?>");
                        });
                    }
                }
            });
        }




        function addTask() {
            taskState = 'Add';
            cleanTasksCRUDFields();
            toggleTaskTypeFields();
            document.getElementById('addTaskModal').classList.add('is-active');
            document.getElementById('taskType').disabled = false;
            createSelfCheckAccordion([], document.getElementById('taskQuestions'));
        }

        function cleanTasksCRUDFields()
        {
            document.getElementById('taskName').value = '';
            document.getElementById('taskType').value = 'PDF';
            document.getElementById('taskFile').value = '';
            document.getElementById('taskFile').nextElementSibling.innerText = '<?php echo $lang['upload_file_for_task']; ?>';
            document.getElementById('correctTaskFile').value = '';
            document.getElementById('correctTaskFile').nextElementSibling.innerText = '<?php echo $lang['upload_correct_solution_file_for_task']; ?>';
            document.getElementById('correctPythonDataFile').value = '';
            document.getElementById('correctPythonDataFile').nextElementSibling.innerText = '<?php echo $lang['upload_python_data_file_for_checking']; ?>';
            document.getElementById('correctPythonProgramExecutionResult').value = '';
            document.getElementById('correctOrangeDataFile').value = '';
            document.getElementById('correctOrangeDataFile').nextElementSibling.innerText = '<?php echo $lang['upload_orange_data_file_for_checking']; ?>';
            document.getElementById('correctOrangeProgramExecutionResult').value = '';
            document.getElementById('taskDescription').value = '';
            document.getElementById('taskSummary').value = '';
            document.getElementById('taskQuestions').value = '';
        }

        function editTask(taskId) {
            // get from fetchedTasks by taskId
            cleanTasksCRUDFields();
            editedTaskID = taskId;
            const task = fetchedTasks.find(task => task.task_id === taskId);
            document.getElementById('taskName').value = task.task_name;
            document.getElementById('taskType').value = task.task_type;
            toggleTaskTypeFields();
            let taskFileSplitted = '';
            if (task.task_file_clean != '' && task.task_file_clean != null) {
                taskFileSplitted = task.task_file_clean.split('/').pop();
            }
       
            if (taskFileSplitted == '') {
                document.getElementById('taskFile').nextElementSibling.innerText = '<?php echo $lang['upload_file_for_task']; ?>';
            } else {
                document.getElementById('taskFile').nextElementSibling.innerText = taskFileSplitted;
                document.getElementById('removeTaskFileButton').style.display = 'block';
            }

            if(task.task_type === 'PDF') {
                const taskQuestionsElement = document.getElementById('taskQuestions');
                questionsGlobal = questionsGlobal.concat(JSON.parse(task.default_self_check_questions).questions);
                createSelfCheckAccordion(questionsGlobal, taskQuestionsElement);
                currentTaskFileUri = task.task_file_uri;
            }

            if (task.task_type === 'Excel') {
                let correctTaskFile = '';
                if (task.task_file_correct != '' && task.task_file_correct != null) {
                    correctTaskFile = task.task_file_correct.split('/').pop();
                }
                if (correctTaskFile === '') {
                    document.getElementById('correctTaskFile').nextElementSibling.innerText = '<?php echo $lang['upload_correct_solution_file_for_task']; ?>';
                } else {
                    document.getElementById('correctTaskFile').nextElementSibling.innerText = correctTaskFile;
                    document.getElementById('removeCorrectTaskFileButton').style.display = 'block';
                }
            }
            if (task.task_type === 'Python') {
                let correctPythonDataFile = '';
                if (task.python_data_file != '' && task.python_data_file != null) {
                    correctPythonDataFile = task.python_data_file.split('/').pop();
                }
                if (correctPythonDataFile === '') {
                    document.getElementById('correctPythonDataFile').nextElementSibling.innerText = '<?php echo $lang['upload_python_data_file_for_checking']; ?>';
                } else {
                    document.getElementById('correctPythonDataFile').nextElementSibling.innerText = correctPythonDataFile;
                    document.getElementById('removePythonDataFileButton').style.display = 'block';
                }
            }
            if (task.task_type === 'Orange') {
                let correctOrangeDataFile = '';
                if (task.orange_data_file != '' && task.orange_data_file != null) {
                    correctOrangeDataFile = task.orange_data_file.split('/').pop();
                }
                if (correctOrangeDataFile === '') {
                    document.getElementById('correctOrangeDataFile').nextElementSibling.innerText = '<?php echo $lang['upload_orange_data_file_for_checking']; ?>';
                } else {
                    document.getElementById('correctOrangeDataFile').nextElementSibling.innerText = correctOrangeDataFile;
                    document.getElementById('removeOrangeDataFileButton').style.display = 'block';
                }
            }
            const correctPythonProgramExecutionResult = task.python_program_execution_result;
            document.getElementById('correctPythonProgramExecutionResult').value = correctPythonProgramExecutionResult;
            const correctOrangeProgramExecutionResult = task.orange_program_execution_result;
            document.getElementById('correctOrangeProgramExecutionResult').value = correctOrangeProgramExecutionResult;
            document.getElementById('taskDescription').value = task.task_text;
            document.getElementById('taskSummary').value = task.default_summary;
            document.getElementById('taskQuestions').value = task.default_self_check_questions;
            
            taskState = 'Edit';

            document.getElementById('addTaskModal').classList.add('is-active');
            document.getElementById('taskType').disabled = true;
        }

        function deleteTask(taskId) {
            bootbox.confirm({
                title: "<?php echo $lang['delete_task_confirmation_header']; ?>",
                message: "<?php echo $lang['delete_task_confirmation']; ?>",
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (result) {
                    if (result) {
                        showLoadingModal();
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'message=delete-task&task-id=' + taskId + '&class-id=' + currentClassId,
                        })
                        .then(response => response.text())
                        .then(text => {
                            hideLoadingModal();
                            reloadWithTaskId(0);
                        })
                        .catch(error => {
                            console.error('An error occurred:', error);
                            hideLoadingModal();
                        });
                    }
                }
            });
        }

        function removeTaskFile(fieldType) {
            bootbox.confirm({
                title: "<?php echo $lang['confirm_remove_file']; ?>",
                message: "<?php echo $lang['confirm_remove_file']; ?>",
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (result) {
                    if (result) {
                        var selectedFileName = '';
                        if (fieldType === 'regular') {
                            selectedFileName = document.getElementById('taskFile').nextElementSibling.innerText;
                        } else if (fieldType === 'correct-excel') {
                            selectedFileName = document.getElementById('correctTaskFile').nextElementSibling.innerText;
                        } else if (fieldType === 'data-python') {
                            selectedFileName = document.getElementById('correctPythonDataFile').nextElementSibling.innerText;
                        } else if (fieldType === 'data-orange') {
                            selectedFileName = document.getElementById('correctOrangeDataFile').nextElementSibling.innerText;
                        }
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'message=remove-task-file&file-name=' + selectedFileName,
                        })
                        .then(response => response.text())
                        .then(result => {
                            if (result.includes('success')) {
                                bootbox.alert("<?php echo $lang['file_removed']; ?>");
                                if (fieldType === 'regular') {
                                    document.getElementById('taskFile').value = '';
                                    document.getElementById('taskFile').nextElementSibling.innerText = '<?php echo $lang['upload_file_for_task']; ?>';
                                    document.getElementById('removeTaskFileButton').style.display = 'none';
                                }
                                else if (fieldType === 'correct-excel') {
                                    document.getElementById('correctTaskFile').value = '';
                                    document.getElementById('correctTaskFile').nextElementSibling.innerText = '<?php echo $lang['upload_file_for_task']; ?>';
                                    document.getElementById('removeCorrectTaskFileButton').style.display = 'none';
                                }
                                else if (fieldType === 'data-python') {
                                    document.getElementById('correctPythonDataFile').value = '';
                                    document.getElementById('correctPythonDataFile').nextElementSibling.innerText = '<?php echo $lang['upload_file_for_task']; ?>';
                                    document.getElementById('removePythonDataFileButton').style.display = 'none';
                                }
                                else if (fieldType === 'data-orange') {
                                    document.getElementById('correctOrangeDataFile').value = '';
                                    document.getElementById('correctOrangeDataFile').nextElementSibling.innerText = '<?php echo $lang['upload_file_for_task']; ?>';
                                    document.getElementById('removeOrangeDataFileButton').style.display = 'block';
                                }
                            }
                            else {
                                bootbox.alert("<?php echo $lang['error_removing_file']; ?>");
                            }

                        })
                        .catch(error => {
                            bootbox.alert("<?php echo $lang['error_removing_file']; ?>");
                        });
                    }
                }
            });
        }

        function changeApiKey()
        {
            bootbox.prompt({
                title: "<?php echo $lang['change_api_key']; ?>",
                inputType: 'password',
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (result) {
                    bootbox.confirm({
                        title: "<?php echo $lang['confirm_change_api_key']; ?>",
                        message: "<?php echo $lang['confirm_change_api_key']; ?>",
                        buttons: {
                            cancel: {
                                label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                            },
                            confirm: {
                                label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                            }
                        },
                        callback: function (confirmed) {
                            if(confirmed) {
                                fetch('', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: 'message=change-api-key&new_api_key=' + result,
                                })
                                .then(response => response.text())
                                .then(result => {
                                    if(result.includes("success")) {
                                        bootbox.alert("<?php echo $lang['api_key_changed']; ?>");
                                    } else {
                                        bootbox.alert("<?php echo $lang['error_changing_api_key']; ?>");
                                    }
                                })
                                .catch(error => {
                                    bootbox.alert("<?php echo $lang['error_changing_api_key']; ?>");
                                });
                            }
                        }
                    });
                }
            });

        }

        function changePassword() {
            bootbox.prompt({
                title: "<?php echo $lang['enter_old_password']; ?>",
                inputType: 'password',
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (oldPassword) {
                    if (oldPassword) {
                        bootbox.prompt({
                            title: "<?php echo $lang['enter_new_password']; ?>",
                            inputType: 'password',
                            buttons: {
                                cancel: {
                                    label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                                },
                                confirm: {
                                    label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                                }
                            },
                            callback: function (newPassword) {
                                if (newPassword) {
                                    bootbox.confirm({
                                        title: "<?php echo $lang['confirm_change_password']; ?>",
                                        message: "<?php echo $lang['confirm_change_password']; ?>",
                                        buttons: {
                                            cancel: {
                                                label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                                            },
                                            confirm: {
                                                label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                                            }
                                        },
                                        callback: function (result) {
                                            if (result) {
                                                fetch('', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/x-www-form-urlencoded',
                                                    },
                                                    body: 'message=change-password&old_password=' + encodeURIComponent(oldPassword) + '&new_password=' + encodeURIComponent(newPassword),
                                                })
                                                .then(response => response.text())
                                                .then(result => {
                                                    if(result.includes("success")) {
                                                        bootbox.alert("<?php echo $lang['password_changed']; ?>");
                                                    } else {
                                                        bootbox.alert("<?php echo $lang['error_changing_password']; ?>");
                                                    }
                                                })
                                                .catch(error => {
                                                    bootbox.alert("<?php echo $lang['error_changing_password']; ?>");
                                                });
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    }
                }
            });
        }

        function addNewClass() {
            bootbox.prompt({
            title: "<?php echo $lang['enter_class_name']; ?>",
            inputType: 'text',
            buttons: {
                cancel: {
                label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                },
                confirm: {
                label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                }
            },
            callback: function (className) {
                if (className) {
                    fetch('', {
                        method: 'POST',
                        headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'message=add-new-class&class_name=' + encodeURIComponent(className),
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result && result !== 'false') {
                            bootbox.alert({
                                message: "<?php echo $lang['class_added_successfully']; ?>",
                                callback: function() {
                                    getClassList(); // Refresh the class list
                                    reloadWithTaskId(0);
                                }
                            });
                        } else {
                            bootbox.alert({
                                message: "<?php echo $lang['error_adding_class']; ?>"
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        bootbox.alert({
                            message: "<?php echo $lang['error_adding_class']; ?>"
                        });
                    });
                }
            }
            });
        }

        function deleteClass(classId, className)
        {
            bootbox.confirm({
                title: "<?php echo $lang['confirm_delete_class']; ?> " + className + "?",
                message: "<?php echo $lang['confirm_delete_class']; ?> " + className + "?",
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (result) {
                    if(result) {
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'message=delete-class&class_id=' + classId,
                        })
                        .then(response => response.text())
                        .then(result => {
                            getClassList();
                            bootbox.alert("<?php echo $lang['class_deleted']; ?>");
                        })
                        .catch(error => {
                            bootbox.alert("<?php echo $lang['error_deleting_class']; ?>");
                        });
                    }
                }
            });
        }

        function editClass(classId, className)
        {
            bootbox.prompt({
                title: "<?php echo $lang['enter_new_class_name']; ?>",
                inputType: 'text',
                value: className,
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (newClassName) {
                    if (newClassName) {
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'message=edit-class&class_id=' + classId + '&class_name=' + encodeURIComponent(newClassName),
                        })
                        .then(response => response.text())
                        .then(result => {
                            getClassList();
                            bootbox.alert("<?php echo $lang['class_edited']; ?>");
                        })
                        .catch(error => {
                            bootbox.alert("<?php echo $lang['error_editing_class']; ?>");
                        });
                    }
                }
            });
        }

        function selectClassWithReload(classId)
        {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=select-class&class_id=' + classId,
            })
            reloadWithTaskId(0);
        }

        function getTiedRequestsCount()
        {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=get-tied-requests-count',
            })
            .then(response => response.text())
            .then(result => {
                const badge = document.getElementById('classNotificationBadge');
                const defaultBadge = document.getElementById('default-class-badge');
                if (!result.includes("none")) {
                    badge.style.display = 'inline';
                    badge.textContent = '  ' + result;
                    bootbox.alert("<?php echo $lang['you_have_student_class_join_requests_accept_them_in_class_list']; ?><br><strong><?php echo $lang['student_class_join_requests_count']; ?>: " + result + "</strong>");
                    defaultBadge.style.display = 'inline';
                    defaultBadge.textContent = result;
                } else {
                    badge.style.display = 'none';
                    badge.textContent = ' 0';
                    defaultBadge.style.display = 'none';
                    defaultBadge.textContent = ' 0';

                }
            })
            .catch(error => {
                console.error('An error occurred:', error);
            });
        }

        function importTasks()
        {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.zip';
            
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);
                formData.append('message', 'import-tasks');
                formData.append('class_id', currentClassId);

                showLoadingModal();
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    hideLoadingModal();
                    if (result.includes('success')) {
                        bootbox.alert("<?php echo $lang['tasks_imported_successfully']; ?>");
                        getTasksList(currentClassId);
                    } else {
                        bootbox.alert("<?php echo $lang['error_importing_tasks']; ?>");
                    }
                })
                .catch(error => {
                    hideLoadingModal();
                    bootbox.alert("<?php echo $lang['error_importing_tasks']; ?>");
                });
            };

            input.click();
        }

        function exportTasks()
        {
            window.location.href = window.location.href + '?itaiassistant101_download_task_data=1&classId=' + currentClassId;
        }


        function closeModal() {
            bootbox.confirm({
                title: "<?php echo $lang['confirm_close']; ?>",
                message: "<?php echo $lang['confirm_close_without_saving']; ?>",
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (result) {
                    if(result) {
                        document.getElementById('addTaskModal').classList.remove('is-active');
                        currentStep = 1;
                        document.getElementById('step1').style.display = 'block';
                        document.getElementById('step2').style.display = 'none';
                        document.getElementById('step3').style.display = 'none';
                        document.getElementById('step4').style.display = 'none';
                        updateFooterButtons(currentStep, 'PDF');
                        deleteFilesOnCloseModal();
                    }
                }
            });
        }

        function deleteFilesOnCloseModal() {
            if (tasksModalState === 'Add') {
                const formData = new FormData();
                formData.append('message', 'delete-task-files-on-close');
                formData.append('current-task-file-path', currentTaskFilePath);
                formData.append('current-correct-task-file-path', currentCorrectTaskFilePath);
                formData.append('current-python-data-file-path', currentPythonDataFilePath);
                formData.append('current-orange-data-file-path', currentOrangeDataFilePath);
                formData.append('class-id', currentClassId);
                fetch('', {
                    method: 'POST',
                    body: formData
                });
            }
        }

        let currentStep = 1;
        updateFooterButtons(currentStep, 'PDF');

        const visibilities = {
            PDF: [1, 2, 3, 4],
            Excel: [1, 2],
            Python: [1, 2],
            Orange: [1, 2]
        };

        function toggleTaskTypeFields() {
            const taskType = document.getElementById('taskType').value;
            const pdfFields = document.querySelectorAll('.pdf-field');
            const excelFields = document.querySelectorAll('.excel-field');
            const pythonFields = document.querySelectorAll('.python-field');
            const orangeFields = document.querySelectorAll('.orange-field');
            const taskFile = document.getElementById('taskFile');
            const taskFileCorrect = document.getElementById('correctTaskFile');
            const fileInput = document.getElementById('fileInput');
            const taskFileDiv = document.getElementById('taskFileField');

            pdfFields.forEach(field => {
                field.style.display = taskType === 'PDF' ? 'block' : 'none';
            });
            excelFields.forEach(field => {
                field.style.display = taskType === 'Excel' ? 'block' : 'none';
            });
            pythonFields.forEach(field => {
                field.style.display = taskType === 'Python' ? 'block' : 'none';
            });
            orangeFields.forEach(field => {
                field.style.display = taskType === 'Orange' ? 'block' : 'none';
            });
        
            if (taskType === 'PDF' || taskType === 'Python' || taskType === 'Orange') {
                taskFile.setAttribute('accept', '.pdf');
                taskFileDiv.style.display = 'block';
            } else if (taskType === 'Excel') {
                taskFile.setAttribute('accept', '.xls,.xlsx');
                taskFileCorrect.setAttribute('accept', '.xls,.xlsx');
                taskFileDiv.style.display = 'block';
            } 
            
            document.getElementById('taskFile').value = '';   
            document.getElementById('taskFile').nextElementSibling.innerText = '<?php echo $lang['upload_file_for_task']; ?>';
            document.getElementById('correctTaskFile').value = '';
            document.getElementById('correctTaskFile').nextElementSibling.innerText = '<?php echo $lang['upload_correct_solution_file_for_task']; ?>';
        }

        function toggleLoadTaskTypeFields()
        {
            taskType = currentTaskJson.task_type;
            const taskFile = document.getElementById('fileInput');
            const fileInputTaskDoneLabel = document.getElementById('fileInputTaskDoneLabel');
            if (taskType === 'Python') {
                fileInput.setAttribute('accept', '.py,.txt');
                fileInputTaskDoneLabel.innerText = '<?php echo $lang['upload_python_file']; ?>';
            }
            if (taskType === 'Orange') {
                // upload images
                fileInput.setAttribute('accept', '.png,.jpg,.jpeg');
                fileInputTaskDoneLabel.innerText = '<?php echo $lang['upload_orange_screenshot_file']; ?>';

            }
            if (taskType === 'PDF') {
                document.getElementById('useDocumentInformation').disabled = true;
            }
        }

        function nextStep() {
            const taskType = document.getElementById('taskType').value;
            const steps = visibilities[taskType];

            if (steps[currentStep - 1] === 1) {
                const fileInput = document.getElementById('taskFile');
                const fileName = fileInput.labels[0].innerText;
                const correctFileInput = document.getElementById('correctTaskFile');
                const correctFileName = correctFileInput.labels[0].innerText;
                const pythonDataFileInput = document.getElementById('correctPythonDataFile');
                const pythonDataFileName = pythonDataFileInput.labels[0].innerText;
                const orangeDataFileInput = document.getElementById('correctOrangeDataFile');
                const orangeDataFileName = orangeDataFileInput.labels[0].innerText;
                document.getElementById('displayTaskFile').innerText = fileName;
                document.getElementById('displayTaskCorrectFile').innerText = correctFileName;
                document.getElementById('displayTaskPythonDataFile').innerText = pythonDataFileName;
                document.getElementById('displayTaskPythonProgramExecutionResult').innerText = document.getElementById('correctPythonProgramExecutionResult').value;
                document.getElementById('displayTaskOrangeDataFile').innerText = orangeDataFileName;
                document.getElementById('displayTaskOrangeProgramExecutionResult').innerText = document.getElementById('correctOrangeProgramExecutionResult').value;

                document.getElementById('displayTaskName').innerText = document.getElementById('taskName').value;
                document.getElementById('displayTaskType').innerText = document.getElementById('taskType').value;
                document.getElementById('displayTaskDescription').innerText = document.getElementById('taskDescription').value;
                
                if(document.getElementById('displayTaskName').innerText === '' || document.getElementById('displayTaskType').innerText === '' || document.getElementById('displayTaskDescription').innerText === '') {
                    bootbox.alert('<?php echo $lang['please_fill_in_all_fields']; ?>');
                    return;
                }

                fileInputText = document.getElementById('taskFile').nextElementSibling.innerText;
                correctFileInputText = document.getElementById('correctTaskFile').nextElementSibling.innerText;                
                if ((fileInputText == "" || fileInputText == null) &&  (taskType === 'PDF' || taskType === 'Excel') && taskState === 'Add') {
                    bootbox.alert('<?php echo $lang['please_fill_in_all_fields']; ?>');
                    return;
                }


                if (taskType === 'Excel' &&  (correctFileInputText == "" || correctFileInputText == null)) {
                    bootbox.alert('<?php echo $lang['please_fill_in_all_fields']; ?>');
                    return;
                }

                if (taskType === 'Python' && (document.getElementById('correctPythonProgramExecutionResult').value === '')) {
                    bootbox.alert('<?php echo $lang['please_fill_in_all_fields']; ?>');
                    return;
                }
            }

            if (steps[currentStep - 1] === 2) {
                if (taskType === 'PDF') {
                    document.getElementById('step3').style.display = 'block';
                    document.getElementById('step2').style.display = 'none';
                    currentStep++;
                    updateFooterButtons(currentStep, taskType);
                    return;

                }
            }

            if (steps[currentStep - 1] === 3) {
                if (taskType === 'PDF') {
                    if (document.getElementById('taskSummary').value === '' || document.getElementById('taskQuestions').value === '') {
                        bootbox.confirm({
                            title: "<?php echo $lang['confirm_proceed']; ?>",
                            message: "<?php echo $lang['confirm_leave_empty_summary']; ?>",
                            buttons: {
                                cancel: {
                                    label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                                },
                                confirm: {
                                    label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                                }
                            },
                            callback: function (result) {
                                if(!result) {
                                    previousStep();
                                    return;
                                } 
                            }
                        });
                    }
                }
            }

            if (steps[currentStep - 1] === 4 || steps[currentStep - 1] === 3) {
                const fileInput = document.getElementById('taskFile');
                const fileName = fileInput.labels[0].innerText;
                const correctFileInput = document.getElementById('correctTaskFile');
                const correctFileName = correctFileInput.labels[0].innerText;
                document.getElementById('displayTaskFile2').innerText = fileName;
                document.getElementById('finalTaskName').innerText = document.getElementById('taskName').value;
                document.getElementById('finalTaskType').innerText = document.getElementById('taskType').value;
                document.getElementById('finalTaskDescription').innerText = document.getElementById('taskDescription').value;
                document.getElementById('finalTaskSummary').innerText = document.getElementById('taskSummary').value;
                                // create a new div element with custom id and add that inside finalTaskQuestions
                const finalQuestionsContainer = document.createElement('div');
                finalQuestionsContainer.id = 'finalQuestionsContainer';
                document.getElementById('finalTaskQuestions').appendChild(finalQuestionsContainer);
                createSelfCheckAccordion(questionsGlobal, finalQuestionsContainer, true);
                
                document.getElementById('step4').style.display = 'block';
                document.getElementById('step3').style.display = 'none';

                currentStep++;
                updateFooterButtons(currentStep, taskType);
                return;
            }

            document.getElementById('step' + steps[currentStep - 1]).style.display = 'none';
            currentStep++;
            document.getElementById('step' + steps[currentStep - 1]).style.display = 'block';
            updateFooterButtons(currentStep, taskType);
        }

        function previousStep() {
            const taskType = document.getElementById('taskType').value;
            const steps = visibilities[taskType];

            document.getElementById('step' + steps[currentStep - 1]).style.display = 'none';
            currentStep--;
            document.getElementById('step' + steps[currentStep - 1]).style.display = 'block';
            updateFooterButtons(currentStep, taskType);
        }

        function updateFooterButtons(step, taskType) {
            document.getElementById('prevButton').style.display = step > 1 ? 'inline' : 'none';
            if (taskType === 'PDF') {
                document.getElementById('saveButton').style.display = step === 4 ? 'inline' : 'none';
                document.getElementById('nextButton').style.display = step < 4 ? 'inline' : 'none';
            } else {
                document.getElementById('saveButton').style.display = step === 2 ? 'inline' : 'none';
                document.getElementById('nextButton').style.display = step < 2 ? 'inline' : 'none';
            }
        }

        function saveTask() {
            const taskName = document.getElementById('taskName').value;
            const taskType = document.getElementById('taskType').value;
            const taskDescription = document.getElementById('taskDescription').value;
            const pythonProgramExecutionResult = document.getElementById('correctPythonProgramExecutionResult').value;
            const orangeProgramExecutionResult = document.getElementById('correctOrangeProgramExecutionResult').value;
            const taskSummary = document.getElementById('taskSummary').value;
            const taskQuestions = document.getElementById('taskQuestions').value;
            let newTaskId = 0;
            if (taskState === 'Add') {
                taskMessage = 'task-save';
            } else {
                taskMessage = 'task-update';
            }

            // Create the data object
            const data = {
                message: taskMessage,
                name: taskName,
                text: taskDescription,
                type: taskType,
                class_id: currentClassId,
                task_id: editedTaskID,
                file_clean: currentTaskFilePath,
                file_correct: currentCorrectTaskFilePath,
                python_data_file: currentPythonDataFilePath,
                orange_data_file: currentOrangeDataFilePath,
                file_uri: currentTaskFileUri,
                correct_file_uri: currentCorrectTaskFileUri,
                python_data_file_uri: currentPythonDataFileUri,
                orange_data_file_uri: currentOrangeDataFileUri,
                python_program_execution_result: pythonProgramExecutionResult,
                orange_program_execution_result: orangeProgramExecutionResult,
                system_prompt: taskSummary,
                default_summary: taskSummary,
                default_self_check_questions: taskQuestions,
                task_file_changed: taskFileChanged,
                correct_task_file_changed: correctTaskFileChanged,
                python_data_file_changed: pythonDataFileChanged,
                orange_data_file_changed: orangeDataFileChanged
            };

            // Send the data using fetch
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
            .then(response => response.json())
            .then(data => {
                bootbox.alert('<?php echo $lang['task_created_successfully']; ?>');
                newTaskId = data;
            })
            .catch((error) => {
                bootbox.alert('<?php echo $lang['error_saving_task']; ?>: ', error);
            });

            // Add the new task to the task list
            const taskList = document.querySelector('.task-list');
            const newTaskItem = document.createElement('div');
            newTaskItem.classList.add('task-item');
            newTaskItem.textContent = taskName;
            taskList.appendChild(newTaskItem);
            newTaskItem.id = 'task-' + newTaskId;
            newTaskItem.onclick = function() {
                reloadWithTaskId(newTaskId);
            };

            reloadWithTaskId(newTaskId);

            // Close the modal
            closeModal();
            currentStep = 1;
        }



        // Initialize modal fields visibility
        toggleTaskTypeFields();

        function openClassModal() {
            document.getElementById('addClassModal').classList.add('is-active');
        }

        function closeClassModal() {
            document.getElementById('addClassModal').classList.remove('is-active');
        }

        function openUserListModal() {
            document.getElementById('userListModal').classList.add('is-active');
        }

        function closeUserListModal() {
            document.getElementById('userListModal').classList.remove('is-active');
        }

        function backToClassList()
        {
            closeUserListModal();
            openClassModal();
        }

        function openSettingsModal() {
            document.getElementById('settingsModal').classList.add('is-active');
        }

        function closeSettingsModal() {
            document.getElementById('settingsModal').classList.remove('is-active');
        }

        function selectClass(classId) {
            closeClassModal();
            getClassUserList(classId);
            <?php if ($current_user->user_role === 'teacher') { ?>
                checkIfClassIsNotMainForTeacher(classId);
            <?php } else { ?>
                openUserListModal();
            <?php } ?>
        }

        function openAddUsersModal(classId) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=get-teacher-unadded-students&class_id=' + classId,
            })
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    bootbox.alert('<?php echo $lang['no_students_to_add']; ?>');
                    return;
                }
                const inputOptions = data.map(user => ({
                    text: user.user_username + ' ' + user.user_name + ' ' + user.user_surname,
                    value: user.user_username
                }));
                bootbox.prompt({
                    title: "<?php echo $lang['select_students_to_add']; ?>",
                    inputType: 'select',
                    multiple: true,
                    value: [],
                    inputOptions: inputOptions,
                    callback: function (result) {
                        if (result) {
                            fetch('', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'message=add-users-to-class&class_id=' + classId + '&users=' + JSON.stringify(result),
                            })
                            .then(response => response.text())
                            .then(result => {
                                if (result.includes("success")) {
                                    getClassUserList(classId);
                                    bootbox.alert("<?php echo $lang['users_added_to_class_successfully']; ?>");
                                } else {
                                    bootbox.alert("<?php echo $lang['error_adding_users_to_class']; ?>");
                                }
                            })
                            .catch(error => {
                                bootbox.alert("<?php echo $lang['error_adding_users_to_class']; ?>");
                            });
                        }
                    }
                });
            })
            .catch(error => {
                console.error(error);
            });
        }

        function createSelfCheckAccordion(questions, elementToAddAfter, displayOnly = false) {

            existingAccordion_cards = document.getElementsByClassName('ITAIAssistant101_accordion_card');
            
            let accordionContainer = null;

            if (displayOnly) {
                accordionContainer = document.getElementById('accordionDisplayOnly');
            }
            else {
                accordionContainer = document.getElementById('accordion');
            }
            let strongText = null;
            if (accordionContainer != null && !displayOnly) {
                accordionContainer.innerHTML = '';
            }

            strongText = document.createElement('strong');
            strongText.innerText = '<?php echo $lang['self_check_questions']; ?>:';
            strongText.id = 'selfCheckQuestionsText_itaiassistant101';


            if (existingAccordion_cards.length == 0) {
                elementToAddAfter.parentNode.insertBefore(strongText, elementToAddAfter.nextSibling);
                accordionContainer = document.createElement('div');
                if (displayOnly) {
                    accordionContainer.id = 'accordionDisplayOnly';
                }
                else {
                    accordionContainer.id = 'accordion';
                }
            }
            else {
                if (!displayOnly) {
                    accordionContainer = existingAccordion_cards[0].parentNode;
                    accordionContainer.innerHTML = '';
                }
                else {
                    accordionContainers = document.querySelectorAll('[id=accordionDisplayOnly]');
                    for (let i = 0; i < accordionContainers.length; i++) {
                        accordionContainers[i].remove();
                    }
                    accordionContainer = document.createElement('div');
                    accordionContainer.id = 'accordionDisplayOnly';
                }
            }

            questions.forEach((q, index) => {
                // print out the whole q,decoded

                const card = document.createElement('div');
                card.classList.add('card');

                if (!displayOnly) {
                    card.classList.add('ITAIAssistant101_accordion_card');
                }

                const cardHeader = document.createElement('div');
                cardHeader.classList.add('card-header');
                cardHeader.id = `heading${index}`;

                const h5 = document.createElement('h5');
                h5.classList.add('mb-0');

                const button = document.createElement('button');
                button.classList.add('btn', 'btn-link', 'collapsed');
                button.setAttribute('data-toggle', 'collapse');
                button.setAttribute('data-target', `#collapse${index}`);
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('aria-controls', `collapse${index}`);
                button.innerText = q.question;

                h5.appendChild(button);
                cardHeader.appendChild(h5);
                card.appendChild(cardHeader);

                const collapseDiv = document.createElement('div');
                collapseDiv.id = `collapse${index}`;
                collapseDiv.classList.add('collapse');
                collapseDiv.setAttribute('aria-labelledby', `heading${index}`);
                collapseDiv.setAttribute('data-parent', '#' + accordionContainer.id);

                const cardBody = document.createElement('div');
                cardBody.classList.add('card-body');
                cardBody.innerText = q.answer;

                collapseDiv.appendChild(cardBody);
                card.appendChild(collapseDiv);
                accordionContainer.appendChild(card);

                if (!displayOnly) {
                    // Add edit and delete buttons
                    const editButton = document.createElement('button');
                    editButton.classList.add('btn','btn-warning','btn-sm');
                    editButton.style.margin = '0.2rem';
                    editButton.innerText = '<?php echo $lang['edit']; ?>';
                    editButton.addEventListener('click', function() {
                        bootbox.prompt({
                            title: "<?php echo $lang['enter_question']; ?>",
                            value: q.question,
                            callback: function(newQuestion) {
                                if (newQuestion !== null) {
                                    bootbox.prompt({
                                        title: "<?php echo $lang['enter_answer']; ?>",
                                        value: q.answer,
                                        callback: function(newAnswer) {
                                            if (newAnswer !== null) {
                                                questions[index].question = newQuestion;
                                                questions[index].answer = newAnswer;
                                                questions[index].order_number = index + 1.0;
                                                questions.forEach((q, i) => {
                                                    q.order_number = i + 1.0;
                                                });
                                                questionsGlobal = questions;
                                                document.getElementById('taskQuestions').value = JSON.stringify({ questions }, null, 2);
                                                createSelfCheckAccordion(questions, elementToAddAfter);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    });
                    cardBody.appendChild(editButton);
                    const deleteButton = document.createElement('button');
                    deleteButton.classList.add('btn','btn-danger','btn-sm');
                    deleteButton.style.margin = '0.2rem';
                    deleteButton.innerText = '<?php echo $lang['delete']; ?>';
                    deleteButton.addEventListener('click', function() {
                        bootbox.confirm("<?php echo $lang['confirm_delete_question']; ?>", function(result) {
                            if(result) {
                                questions.splice(index, 1);
                                questions.forEach((q, i) => {
                                    q.order_number = i + 1.0;
                                });
                                questionsGlobal = questions;
                                document.getElementById('taskQuestions').value = JSON.stringify({ questions }, null, 2);
                                createSelfCheckAccordion(questions, elementToAddAfter);
                            }
                        });
                    });
                    cardBody.appendChild(deleteButton);
                }
            });

            if (!displayOnly) {
                // Add button to create new question
                const createButton = document.createElement('button');
                createButton.classList.add('btn','btn-success','btn-sm');
                createButton.style.margin = '0.2rem';
                createButton.innerText = '<?php echo $lang['add_question']; ?>';
                createButton.addEventListener('click', function() {
                    bootbox.prompt({
                        title: "<?php echo $lang['enter_question']; ?>",
                        callback: function(newQuestion) {
                            if (newQuestion) {
                                bootbox.prompt({
                                    title: "<?php echo $lang['enter_answer']; ?>",
                                    callback: function(newAnswer) {
                                        if (newAnswer) {
                                            questions.forEach((q, i) => {
                                                q.order_number = i + 1.0;
                                            });
                                            questions.push({ question: newQuestion, answer: newAnswer, order_number: questions.length + 1.0 });
                                            questionsGlobal = questions;
                                            document.getElementById('taskQuestions').value = JSON.stringify({ questions }, null, 2);
                                            createSelfCheckAccordion(questions, elementToAddAfter);
                                        }
                                    }
                                });
                            }
                        }
                    });
                });
                accordionContainer.appendChild(createButton);
            }

            elementToAddAfter.parentNode.insertBefore(accordionContainer, strongText.nextSibling);
            strongTextElements = document.querySelectorAll('[id=selfCheckQuestionsText_itaiassistant101]');
            // delete all except the first one
            for (let i = 1; i < strongTextElements.length; i++) {
                strongTextElements[i].remove();
            }
            hideLoadingModal();
        }

        function reloadWithTaskId(taskId) {
            window.location.href = window.location.pathname + '?task_id=' + taskId;
        }

        function confirmLogout() {
            bootbox.confirm({
                title: "<?php echo $lang['logout_confirmation_header']; ?>",
                message: "<?php echo $lang['logout_confirmation']; ?>",
                buttons: {
                    cancel: {
                        label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                    },
                    confirm: {
                        label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                    }
                },
                callback: function (result) {
                    if (result) {
                        window.location.href = "<?php echo home_url('/itaiassistant101/logout'); ?>";
                    }
                }
            });
        }

        function addPasswordToggle(inputElement) {
            const passwordToggleIcon = document.createElement('i');
            passwordToggleIcon.classList.add('fa', 'fa-eye-slash', 'password-toggle');
            passwordToggleIcon.style.position = 'absolute';
            passwordToggleIcon.style.right = '20px';
            passwordToggleIcon.style.top = '10px';
            passwordToggleIcon.style.cursor = 'pointer';
            
            inputElement.insertAdjacentElement('afterend', passwordToggleIcon);

            passwordToggleIcon.addEventListener('click', function () {
                if (inputElement.type === "password") {
                    inputElement.type = "text";
                    this.classList.remove("fa-eye-slash");
                    this.classList.add("fa-eye");
                } else {
                    inputElement.type = "password";
                    this.classList.remove("fa-eye");
                    this.classList.add("fa-eye-slash");
                }
            });
        }

        function handleSwitchChange(element) {
            if (element.checked) {
                usingExternalInfo = true;
                // Add actions to perform when the switch is ON
            } else {
                usingExternalInfo = false;
                // Add actions to perform when the switch is OFF
            }
        }

        document.getElementById('fileInput').addEventListener('change', function() {
            var fileName = this.files[0].name;
            var nextSibling = this.nextElementSibling;
            nextSibling.innerText = fileName;
        });

        document.getElementById('taskFile').addEventListener('change', function() {
            var fileName = this.files[0].name;
            var nextSibling = this.nextElementSibling;
            nextSibling.innerText = fileName;
            document.getElementById('removeTaskFileButton').style.display = 'block';
            taskFileChanged = true;
        });

        document.getElementById('correctTaskFile').addEventListener('change', function() {
            var fileName = this.files[0].name;
            var nextSibling = this.nextElementSibling;
            nextSibling.innerText = fileName;
            document.getElementById('removeCorrectTaskFileButton').style.display = 'block';
            correctTaskFileChanged = true;
        });

        document.getElementById('correctPythonDataFile').addEventListener('change', function() {
            var fileName = this.files[0].name;
            var nextSibling = this.nextElementSibling;
            nextSibling.innerText = fileName;
            document.getElementById('removePythonDataFileButton').style.display = 'block';
            pythonDataFileChanged = true;
        });

        document.getElementById('correctOrangeDataFile').addEventListener('change', function() {
            var fileName = this.files[0].name;
            var nextSibling = this.nextElementSibling;
            nextSibling.innerText = fileName;
            document.getElementById('removeOrangeDataFileButton').style.display = 'block';
            orangeDataFileChanged = true;
        });

        userInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        });

        window.addEventListener("load", function() {
            setTimeout(function() {
                // php check if $username from $userManager is teacher or student
                <?php if ($current_user->user_role === 'teacher') { ?>
                    // hide elements by class name teachers-buttons
                    var elements = document.getElementsByClassName("teachers-buttons");
                    for (var i = 0; i < elements.length; i++) {
                        elements[i].style.display = 'block';
                    }
                <?php }?>
                getTiedRequestsCount();
                sendIntroMessage();
                getCurrentClassId();
                getCurrentTask();
            }, 2000); // 2000 milliseconds = 2 seconds
        });

        // Use MutationObserver to detect when the prompt is inserted into the DOM
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(addedNode) {
                    if (addedNode.querySelector) {
                        const passwordInput = addedNode.querySelector('.bootbox-input-password');
                        if (passwordInput) {
                            addPasswordToggle(passwordInput);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
        
    </script>
</body>
</html>
