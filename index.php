
<?php

session_start();
require_once 'APIConnector.php';
require_once 'ClassManager.php';
require_once 'UserManager.php';
require_once 'ExcelReader.php';
require_once 'TaskData.php';
require_once 'PDFReader.php';
require_once 'TaskManager.php';

$api_connector = new ApiConnector('');
// $classManager = new ClassManager();
$user_manager = new UserManager();
$current_task = "";
$taskManager = new TaskManager();

if(isset($_GET['task_id'])){
    $current_task = $taskManager->get_task($_GET['task_id']);
}
else {
    $current_task = "No task selected";
}
$username = '';
$user_excel_string = '';
$pdf_document_talking = true;
$correct_excel_sting = ' [A1] => Stačiakampio plotas S = [B1] => [C1] => [D1] => [E1] => 140 [F1] => dm2 [G1] => [H1] => [I1] => [J1] => [A2] => a, dm [B2] => 2 [C2] => 1 [D2] => 7 [E2] => 28 [F2] => 14 [G2] => 1.4 [H2] => [I2] => [J2] => [A3] => b, dm [B3] => =E1/B2 ( 70 ) [C3] => =E1/C2 ( 140 ) [D3] => =E1/D2 ( 20 ) [E3] => =E1/E2 ( 5 ) [F3] => =E1/F2 ( 10 ) [G3] => =E1/G2 ( 100 ) [H3] => [I3] => [J3] => ';

if (isset($_SESSION['jwt_token'])) {
    error_log('jwt_token found');
    $jwt_token = $_SESSION['jwt_token'];
    $decoded_token = $api_connector->verify_jwt($jwt_token);
    if ($decoded_token) {
        error_log('Token valid');
        $username = $decoded_token->data->username;
        $userType = $decoded_token->data->user_type;
        $current_user = $user_manager->get_user_by_username($username);
        $class_id = $current_user->last_used_class_id;
        if (!$class_id) {
            wp_redirect(home_url('/itaiassistant101/dashboard'));
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
// OpenAI API settings
$API_KEY = $user_manager->get_class_API_key($class_id);


if (!isset($_SESSION['chat_parameters'])) {
    $_SESSION['chat_parameters'] = [
        "system_instruction" => [
            "parts" => [
                "text" => $current_task->system_prompt
            ]
        ],
        "contents" => []
    ];
}


// Function to send a request to the OpenAI API
function callOpenAI($endpoint, $data) {
    global $user_excel_string;
    global $correct_excel_sting;
    global $current_task;
    global $pdf_document_talking;
    if ($data['messages'][count($data['messages']) - 1]['content'] == "clear-chat"){
        $_SESSION['chat_parameters'] = [
            "system_instruction" => [
                "parts" => [
                    "text" => $current_task->system_prompt
                ]
            ],
            "contents" => []
        ];
    }

    if($data['messages'][count($data['messages']) - 1]['content'] == "check-excel"){
        error_log('user_excel_string: ' . $user_excel_string);
        if ($user_excel_string == ''){
            return;
        }
        else {
            $data['messages'][count($data['messages']) - 1]['content'] = 'You must compare the result of a student'. $user_excel_string . 'with the correct result' . $correct_excel_sting . 'and provide feedback and useful tips. Make sure you only use the data from' . $user_excel_string .' for the final answer and do NOT use the correct result.';
            $user_excel_string = '';
            error_log($data['messages'][count($data['messages']) - 1]['content']);
        }
    }

    if($pdf_document_talking){
        $relevant_passage = call_embedded_pdf($data['messages'][count($data['messages']) - 1]['content']);
        $_SESSION['chat_parameters'] = [
            "system_instruction" => [
                "parts" => [
                    "text" => "You are a helpful and informative bot that answers questions in lithuanian language using text from the reference passage included below. \
  Be sure to respond in a complete sentence, being comprehensive, including all relevant background information. \
  However, you are talking to a non-technical audience, so be sure to break down complicated concepts and \
  strike a friendly and converstional tone. \
  If the passage is irrelevant to the answer, you may ignore it.
  You should expand the answer a bit using your data outside of the passage if there is too little information in the passage. \
  QUESTION: '" . $data['messages'][count($data['messages']) - 1]['content'] . "'
  PASSAGE: '" . $relevant_passage . "'

    ANSWER:"
                ]
            ],
            "contents" => []
        ];
    }


    global $API_KEY;

    $chat_parameters = $_SESSION['chat_parameters'];

    $headers = [
        'Content-Type: application/json',
    ];

    $chat_parameters['contents'][] = [
        "role" => "user",
        "parts" => [
            "text" => $data['messages'][count($data['messages']) - 1]['content']
        ]
    ];
    
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$API_KEY");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($chat_parameters));

    $response = curl_exec($ch);
    curl_close($ch);

    $assistant_response = json_decode($response);
    $chat_parameters['contents'][] = [
        "role" => "model",
        "parts" => [
            "text" => $assistant_response->candidates[0]->content->parts[0]->text
        ]
    ];

    $_SESSION['chat_parameters'] = $chat_parameters;

    return $assistant_response;
}

function embed_fn($text) {
    global $API_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/embedding-001:embedContent?key=$API_KEY";
    
    // Extract the first 5 words for the title
    // $title = implode(' ', array_slice(explode(' ', $text), 0, 5));  
    
    $data = array(
      'model' => 'models/embedding-001',
      'content' => array(
        'parts' => array(
          array(
            'text' => $text //,
            // 'title' => $title, 
          ),
        ),
      ),
      'task_type' => 'retrieval_document',
    );
  
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
    ));
  
    $response = curl_exec($ch);
    curl_close($ch);
  
    $response = json_decode($response, true);
    return $response['embedding']['values'];
}

function find_best_passage($query, $documents) {
    $queryEmbedding = embed_fn($query);

    $bestPassage = '';
    $maxDotProduct = -1; // Initialize with a value less than any possible dot product

    foreach ($documents as $document) {
        $documentEmbedding = embed_fn($document);

        // Calculate the dot product
        $dotProduct = 0;
        for ($i = 0; $i < count($queryEmbedding); $i++) {
            $dotProduct += $queryEmbedding[$i] * $documentEmbedding[$i];
        }

        // Update best passage if current dot product is higher
        if ($dotProduct > $maxDotProduct) {
            $maxDotProduct = $dotProduct;
            $bestPassage = $document;
        }
    }

    return $bestPassage;
}

// Function to send a request to the OpenAI API regarding ChatGPT
function chatGPT($messages) {
    $endpoint = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-3.5-turbo', // Specify the model to use
        'messages' => $messages,
        'max_tokens' => 500, // Maximum number of tokens for the response (can be changed as needed)
        'temperature' => 0.7, // Diversity of the response (set between 0.0 and 1.0)
        'n' => 1, // Number of responses to generate (can be changed as needed)
    ];

    $response = callOpenAI($endpoint, $data);

    return $response->candidates[0]->content->parts[0]->text;
}

function loadChatHistory(): void {
    if (isset($_SESSION['chat_parameters'])) {
        $chatHistory = $_SESSION['chat_parameters']['contents'];
        foreach ($chatHistory as $message) {
            $role = $message['role'];
            $content = $message['content'];
            echo "displayMessage('$content', '$role');";
        }
    }
}

// Function to process user input and generate a response
function processChatInput($input) {
    // Retrieve conversation history from the session
    session_start();
    $messages = isset($_SESSION['messages']) ? $_SESSION['messages'] : [];

    // Add the user's input to the conversation history
    $messages[] = ['role' => 'user', 'content' => $input];

    // Generate a response from ChatGPT
    $response = chatGPT($messages);

    // Add ChatGPT's response to the conversation history
    $messages[] = ['role' => 'model', 'content' => $response];

    // Save the conversation history to the session
    $_SESSION['messages'] = $messages;

    return $response;
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

function sendModelFile($filePath) {
    if (file_exists($filePath)) {
        // Set headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
    
        // Clean the output buffer
        ob_clean();
    
        flush();

        // Read the file content
        readfile($filePath);
        exit;
    }
}


// Function to handle file uploads
function uploadFile() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
        global $username;
        global $user_excel_string;
        global $API_KEY;
        global $current_task;
        $PDFReader = new PDFReader();
        $folderPath = WP_CONTENT_DIR . '/ITAIAssistant101/' . $username . '/' . 'TASK' . $current_task->task_id;
        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        $file = $_FILES['file'];
        $dateTime = date('YmdHis');
        $filePath = $folderPath . '/' . $username . '_' . $dateTime . '_' . basename($file['name']);  
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // if file extension is excel
            if ($fileExtension == 'xlsx' || $fileExtension == 'xls') {
                $excel_reader = new ExcelReader($filePath);
                $excel_data = $excel_reader->readDataWithCoordinates();
                // move excel_data to text file with the same name to the same path but the extension is .txt
                $textFilePath = str_replace($fileExtension, 'txt', $filePath);
                $textFile = fopen($textFilePath, 'w');
                fwrite($textFile, print_r($excel_data, true));
                fclose($textFile);
                $fileUri = $PDFReader->uploadFileNew($API_KEY, $textFilePath, $fileName . '.txt', 'text/plain');
                return [$filePath, $fileUri]; 
            }
            elseif ($fileExtension == 'pdf') {
                $fileUri = $PDFReader->uploadFileNew($API_KEY, $filePath, $fileName . '.pdf', 'application/pdf');
                return [$filePath, $fileUri];  
            }
            
            // $excel_reader = new ExcelReader($filePath);

            // $excel_data = $excel_reader->readDataWithCoordinates();
            // $user_excel_string = print_r($excel_data, true);
            // $response = processChatInput("check-excel");
            // // echo $response;
            // error_log('resultsss:' . print_r($excel_data, true) . ' ' . $response);
            // return $response;
        } else {
            return '';
        }
    } else {
        return 'Invalid request method or no file uploaded';
    }
}

function call_embedded_pdf($user_message) {
    call_embedded_ocr_pdf($user_message);
    return;

    $filePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/task1.pdf';
    $pdfReader = new PdfReader();
    $text_array = $pdfReader->getTextFromPages($filePath);
    // print_r($text_array);
    // $embeddings = [];
    // foreach ($text_array as $text) {
    //     $embedding = embed_fn($text);
    //     $embeddings[] = $embedding; 
    // }

    // Print the embeddings (or use them for further processing)
    // print_r($embeddings); 

    // merge passages shorter than 500 words
    $text_array = array_reduce($text_array, function($carry, $text) {
        if (empty($carry)) {
            $carry[] = $text;
        } else {
            $lastIndex = count($carry) - 1;
            if (str_word_count($text) < 500) {
                if (str_word_count(string: $carry[$lastIndex]) < 500) {
                    $carry[$lastIndex] .= ' ' . $text;
                } else {
                    $carry[] = $text;
                }
            } else {
                $carry[] = $text;
            }
        }
        return $carry;
    }, []);

    // Find the best passage that matches the user's query
    $bestPassage = find_best_passage($user_message, $text_array);

    // $user_message_embedding = embed_fn($user_message);
    // print_r('best passage: ' . $bestPassage);
    return $bestPassage;

}

function summarize_pdf() {
    $pdf_document_talking = false;
    $filePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/task1.pdf';
    $pdfReader = new PdfReader();
    $text_array = $pdfReader->getTextFromPages($filePath);

    // Merge passages shorter than 500 words
    $text_array = array_reduce($text_array, function($carry, $text) {
        if (empty($carry)) {
            $carry[] = $text;
        } else {
            $lastIndex = count($carry) - 1;
            if (str_word_count($text) < 500) {
                if (str_word_count($carry[$lastIndex]) < 500) {
                    $carry[$lastIndex] .= ' ' . $text;
                } else {
                    $carry[] = $text;
                }
            } else {
                $carry[] = $text;
            }
        }
        return $carry;
    }, []);

    $summaries = [];
    foreach ($text_array as $text) {
        $summaries[] = processChatInput('summarize this: ' . $text);
    }
        
    // if (count($text_array) == 1 && str_word_count($text_array[0]) < 500) {
    //     return $text_array[0];
    // }

    //summarize all the summaries with processChatInput
    $final_summary = '';
    foreach ($summaries as $summary) {
        $final_summary .= $summary;
    }
    $pdf_document_talking = true;
    return processChatInput('summarize this in lithuanian and in detail: ' . $final_summary);
}


function get_example_questions_from_pdf() {
    $pdf_document_talking = false;
    $filePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/task1.pdf';
    $pdfReader = new PdfReader();
    $text_array = $pdfReader->getTextFromPages($filePath);

    // Merge passages shorter than 500 words
    $text_array = array_reduce($text_array, function($carry, $text) {
        if (empty($carry)) {
            $carry[] = $text;
        } else {
            $lastIndex = count($carry) - 1;
            if (str_word_count($text) < 500) {
                if (str_word_count($carry[$lastIndex]) < 500) {
                    $carry[$lastIndex] .= ' ' . $text;
                } else {
                    $carry[] = $text;
                }
            } else {
                $carry[] = $text;
            }
        }
        return $carry;
    }, []);

    $questions = [];
    foreach ($text_array as $text) {
        $questions[] = processChatInput('generate 5 questions from this passage in lithuanian: ' . $text);
    }
        
    // if (count($text_array) == 1 && str_word_count($text_array[0]) < 500) {
    //     return $text_array[0];
    // }

    //summarize all the summaries with processChatInput
    $final_questions = '';
    foreach ($questions as $question) {
        $final_questions .= $question;
    }
    $pdf_document_talking = true;
    return $final_questions;// processChatInput('remove duplicate questions that have the same meaning leaving only one: ' . $final_questions);
}


function call_embedded_ocr_pdf($message, $fileUri = '') { 
    global $API_KEY;
    $pdfReader = new PdfReader();
    echo $pdfReader->analyzePdf($API_KEY, $fileUri, $message);
    return true;
    // return $pdfReader->uploadAndDescribeFile($API_KEY, $filePath, 'downloaded_sc2.png');

}

function saveTask($name, $text, $type, $class_id, $file_clean = null, $file_correct = null, $file_uri = null, $correct_file_uri = null, $system_prompt = null, $default_summary = null, $default_self_check_questions = null) {
    global $taskManager;
    $taskManager->insert_task($name, $text, $type, $class_id, $file_clean, $file_correct, $file_uri, $correct_file_uri, $system_prompt, $default_summary, $default_self_check_questions);
}

// get_tasks_by_class_id
function getTasksByClassId($class_id) {
    global $taskManager;
    return $taskManager->get_tasks_by_class_id($class_id);
}


function convert_path_to_url($full_path) {
    // Split the path to extract the domain name
    $path_parts = explode('/', $full_path);

    // Extract the relevant path after the domain
    $relevant_path = array_slice($path_parts, 6);  // Adjust this according to your structure

    // Construct the URL without encoding the domain and basic structure
    $url = 'https://' . $path_parts[4] . '/' . implode('/', $relevant_path);

    // Encode only the parts of the URL that need it
    $encoded_url = preg_replace_callback('/[^A-Za-z0-9\-\/]/', function($matches) {
        return rawurlencode($matches[0]);
    }, $url);

    return $encoded_url;
}


// Main execution logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message'])) {
        $input = $_POST['message'];
        if ($input === 'intro-message') {
            // sendModelMessage($current_task->getPrompts()[0]);
            // sendModelMessage($current_task->getTaskFileClean());
            // sendModelMessage(summarize_pdf());
            // sendModelMessage(get_example_questions_from_pdf());
            // sendModelFile($current_task->getTaskFileClean());
            if ($current_task->task_id != null) {
                sendModelMessage("**Task name:**<br>{$current_task->task_name}");
                sendModelMessage("**Task text:**<br>{$current_task->task_text}");
                sendModelMessage("**Task file:**<br>" . convert_path_to_url($current_task->task_file_clean));
                if ($current_task->task_type == 'PDF') {
                    sendModelMessage("**Task summary:**<br>{$current_task->default_summary}");
                    sendModelMessage("**Task self-check questions:**<br>{$current_task->default_self_check_questions}");
                }
                // call_embedded_ocr_pdf('Please summarize the text in the PDF file in Lithuanian language');
            }
            else {
                sendModelMessage('No task selected');
            }
        }
        elseif ($input === 'task-summary') {
            // $filePath = urldecode($_POST['filePath']);
            $fileUri = urldecode($_POST['fileUri']);
            if (file_exists($filePath)) {
                error_log("File exists.");
            } 
            else 
            { 
                error_log("File does not exist."); 
            }
            call_embedded_ocr_pdf('Please summarize the text in the PDF file in Lithuanian language', $fileUri);
        }
        elseif($input === 'task-questions') {
            // $filePath = urldecode($_POST['filePath']);
            $fileUri = urldecode($_POST['fileUri']);
            call_embedded_ocr_pdf('Please write some self-check questions with answers from the PDF file in Lithuanian language', $fileUri);
        }
        elseif($input === 'task-save') {
            //saveTask
            $name = $_POST['name'];
            $text = $_POST['text'];
            $type = $_POST['type'];
            $class_id = $_POST['class_id'];
            $file_clean = urldecode($_POST['file_clean']);
            $file_correct = urldecode($_POST['file_correct']);
            $file_uri = urldecode(string: $_POST['file_uri']);
            $correct_file_uri = urldecode($_POST['correct_file_uri']);
            $system_prompt = 'You are a helpful and informative bot that answers questions in lithuanian language using text from the file.';
            $default_summary = $_POST['default_summary'];
            $default_self_check_questions = $_POST['default_self_check_questions'];
            saveTask($name, $text, $type, $class_id, $file_clean, $file_correct, $file_uri, $correct_file_uri, $system_prompt, $default_summary, $default_self_check_questions);
        }
        elseif($input === 'task-list') {
            $class_id = $_POST['class_id'];
            $tasks = getTasksByClassId($class_id);
            echo json_encode($tasks);
        }
        elseif($input === 'task-file') {
            $result = uploadFile();
            echo json_encode($result);
        }
        elseif($input === 'done-task-file') {
            $result = uploadFile();
            $task_id = $_POST['task_id'];
            $class_id = $_POST['class_id'];
            $task = $taskManager->get_task($task_id);
            $user_username = $username;
            $solution_file = $result[0];
            $solution_file_uri = $result[1];
            $taskManager->insert_student_task_solution($task_id, $class_id, $user_username, $solution_file, $solution_file_uri);
            $pdfReader = new PdfReader();
            $prompt = 'Please compare the student solution with the correct solution and provide feedback and useful tips.';
            echo $pdfReader->analyzeExcel($API_KEY, $solution_file_uri, $task->clean_task_file_uri, $prompt);
            return "Your uploaded solution: {$solution_file}";
        }
        elseif($input === 'current-task') {
            $task_id = $_GET['task_id'];
            if($task_id != null) {
                $current_task = $taskManager->get_task($task_id);
                echo json_encode($current_task);
            }
            else {
                echo json_encode('No task selected');
            }
        }
        else {
                if ($current_task->task_type == 'PDF') {
                    $pdfReader = new PdfReader();
                    $fileUri = $current_task->task_file_uri;
                    if (!$pdfReader->fileExists($API_KEY, $fileUri)) {
                        $fileUri = $pdfReader->uploadFileNew($API_KEY, $current_task->task_file_clean, 'task111.pdf');
                        $taskManager->update_task($current_task->task_id, $current_task->task_name, $current_task->task_text, $current_task->task_type, $current_task->task_file_clean, $current_task->task_file_correct, $fileUri, $current_task->system_prompt, $current_task->default_summary, $current_task->default_self_check_questions);
                    }
                    call_embedded_ocr_pdf("Please answer to the user message {$input} from the file in Lithuanian", $fileUri);
                }
                else {
                    $response = processChatInput($input);
                    echo $response;
                }
        }
    } elseif (isset($_FILES['file'])) {
        $result = uploadFile();
        echo json_encode($result);
    }
    exit(); // End the program after sending the response
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Pokalbių robotas</title>
    <script src="https://cdn.jsdelivr.net/npm/marked@3.0.7/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.3/css/bulma.min.css">
    <style>
        .section {
            display: flex;
        }
        .left-panel {
            width: 15%;
            height: 100vh;
            background-color: #f5f5f5;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .container {
            width: 85%;
            margin-left: 1rem;
        }
        .class-selector, .task-list, .settings-menu {
            padding: 1rem;
        }
        .settings-menu {
            padding: 1rem;
            /* display: flex; */
            justify-content: space-between;
        }

        .settings-menu .button {
            flex: 1;
            margin-right: 0.5rem;
        }

        .settings-menu .button:last-child {
            margin-right: 0;
        }
        #task-addition {
            margin-top: 1rem;
        }
        #class-selection .button {
            background-color: #3273dc; /* Blue color for class selection button */
            color: white;
        }

        #task-addition .button {
            background-color: #00d1b2; /* Green color for task addition button */
            color: white;
        }
        .task-list {
            flex-grow: 1;
            overflow-y: auto;
        }
        .task-item {
            padding: 0.5rem;
            cursor: pointer;
        }
        .task-item:hover {
            background-color: #e0e0e0;
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
    </style>
</head>
<body>
    <section class="section">
        <div class="left-panel">
            <div id="class-selection">
                <button class="button is-link" onclick="openClassModal()">Select Class</button>
            </div>
            <div id="task-addition">
                <button class="button is-primary" onclick="addTask()">Add Task</button>
            </div>  
            <div class="task-list">
                <!-- <div class="task-item" onclick="reloadWithTaskId(1)">Task 1</div>
                <div class="task-item" onclick="reloadWithTaskId(2)">Task 2</div>
                <div class="task-item" onclick="reloadWithTaskId(3)">Task 3</div> -->

                <script>
                    function reloadWithTaskId(taskId) {
                        window.location.href = window.location.pathname + '?task_id=' + taskId;
                    }
                </script>
            </div>
            <div class="settings-menu">
                <button class="button is-primary">Settings</button>
            </div>
        </div>
        <div class="container">
            <h1 class="title">Pokalbių robotas</h1>
            <div id="chat-container" class="box"></div>
            <div id="typing-indicator">Assistant is typing...</div>
            <div class="field is-grouped">
                <p class="control is-expanded">
                    <input id="user-input" class="input" type="text" placeholder="Please enter a message">
                </p>
                <p class="control">
                    <button class="button is-primary" onclick="sendMessage()">Send</button>
                </p>
                <p class="control">
                    <input type="file" id="fileInput" accept=".xls,.xlsx" onchange="uploadFile()">
                </p>
            </div>
            <div id="loader">
                <progress class="progress is-small is-primary" max="100"></progress>
            </div>
        </div>
    </section>
    <!-- TODO add file upload and db field for uploaded file path, link to php and sql, add auto fill with gemini -->
    <!-- Add Task Modal -->
    <div class="modal" id="addTaskModal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Add Task</p>
            <button class="delete" aria-label="close" onclick="closeModal()"></button>
        </header>
        <section class="modal-card-body">
            <div id="step1">
                <div class="field">
                    <label class="label">Task Name</label>
                    <div class="control">
                        <input class="input" type="text" id="taskName" placeholder="Enter task name">
                    </div>
                </div>
                <div class="field">
                    <label class="label">Task Type</label>
                    <div class="control">
                        <div class="select">
                            <select id="taskType" onchange="toggleTaskTypeFields()">
                                <option value="PDF">PDF</option>
                                <option value="Excel">Excel</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Upload File</label>
                    <div class="control">
                        <input type="file" id="taskFile" accept=".pdf,.xls,.xlsx" onchange="validateFileType()">
                    </div>
                </div>
                <div class="excel-field">
                    <div class="field">
                        <label class="label">Upload Correct Exercise File</label>
                        <div class="control">
                            <input type="file" id="correctTaskFile" accept=".pdf,.xls,.xlsx" onchange="validateCorrectFileType()">
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Task Description</label>
                    <div class="control">
                        <textarea class="textarea" id="taskDescription" placeholder="Enter task description"></textarea>
                    </div>
                </div>
            </div>
            <div id="step2" style="display: none;">
                <p><strong>Task Name:</strong> <span id="displayTaskName"></span></p>
                <p><strong>Task Type:</strong> <span id="displayTaskType"></span></p>
                <p><strong>Uploaded File:</strong> <span id="displayTaskFile"></span></p>  
                <p><strong>Task Description:</strong> <span id="displayTaskDescription"></span></p>
            </div>
            <div id="step3" style="display: none;">
                <div class="pdf-field">
                    <div class="field">
                        <label class="label">Task Summary</label>
                        <button class="button" onclick="writeTaskSummary()">Write summary with AI</button>
                        <div class="control">
                            <textarea class="textarea" id="taskSummary" placeholder="Enter task summary"></textarea>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label">Task Self Check Questions</label>
                        <button class="button" onclick="writeTaskQuestions()">Write questions with AI</button>
                        <div class="control">
                            <textarea class="textarea" id="taskQuestions" placeholder="Enter task self check questions"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div id="step4" style="display: none;">
                <p><strong>Task Name:</strong> <span id="finalTaskName"></span></p>
                <p><strong>Task Type:</strong> <span id="finalTaskType"></span></p>
                <p><strong>Uploaded File:</strong> <span id="displayTaskFile2"></span></p>  
                <p><strong>Task Description:</strong> <span id="finalTaskDescription"></span></p>
                <p class="pdf-field"><strong>Task Summary:</strong> <span id="finalTaskSummary"></span></p>
                <p class="pdf-field"><strong>Task Self Check Questions:</strong> <span id="finalTaskQuestions"></span></p>
            </div>
        </section>
        <footer class="modal-card-foot">
            <button class="button" onclick="closeModal()">Cancel</button>
            <button class="button" id="prevButton" onclick="previousStep()">Back</button>
            <button class="button is-primary" id="nextButton" onclick="nextStep()">Next</button>
            <button class="button is-success" id="saveButton" onclick="saveTask()">Save Task</button>
        </footer>
        </div>
    </div>

    <!-- Add Class Modal -->
    <div class="modal" id="addClassModal">
        <div class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Select Class</p>
                <button class="delete" aria-label="close" onclick="closeClassModal()"></button>
            </header>
            <section class="modal-card-body">
                <div class="field">
                    <label class="label">Available Classes</label>
                    <div class="control">
                        <div class="list">
                            <div class="list-item" onclick="selectClass('Class 1')">Class 1</div>
                            <div class="list-item" onclick="selectClass('Class 2')">Class 2</div>
                            <div class="list-item" onclick="selectClass('Class 3')">Class 3</div>
                        </div>
                    </div>
                </div>
            </section>
            <footer class="modal-card-foot">
                <button class="button" onclick="closeClassModal()">Close</button>
            </footer>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal" id="loadingModal">
        <div class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Request Loading</p>
            </header>
            <section class="modal-card-body">
                <progress class="progress is-small is-primary" max="100">Loading...</progress>
                <p>Please wait while we process your request.</p>
            </section>
        </div>
    </div>
    <script>
        const chatContainer = document.getElementById('chat-container');
        const userInput = document.getElementById('user-input');
        const loader = document.getElementById('loader');
        const typingIndicator = document.getElementById('typing-indicator');

        let currentTaskJson = '';

        let currentTaskFilePath = '';
        let currentTaskFileUri = '';

        let currentCorrectTaskFilePath = '';
        let currentCorrectTaskFileUri = '';

        function displayMessage(text, sender) {
            console.log(text, sender);
            const messageContainer = document.createElement('div');
            messageContainer.classList.add('message-container', sender);
            const messageBubble = document.createElement('div');
            messageBubble.classList.add('message-bubble', sender);

            // Convert URLs in the text to clickable links
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            const html = marked(text.replace(urlRegex, function(url) {
                return `<a href="${url}" target="_blank">${url}</a>`;
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
                body: 'message=' + encodeURIComponent(message),
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
            // displayMessage('intro-message', 'user');
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
                // displayFileMessage('Task 1 file', text, 'model');
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
            // showLoader();
            // showTypingIndicator();
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
                // displayMessage(text, 'model');
                // hideLoader();
                // hideTypingIndicator();
            })
            .catch(error => {
                console.error('An error occurred:', error);
                hideLoadingModal();
                // hideLoader();
                // hideTypingIndicator();
            });
        }

        function writeTaskQuestions() {
            // showLoader();
            // showTypingIndicator();
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
                hideLoadingModal();
                // displayMessage(text, 'model');
                // hideLoader();
                // hideTypingIndicator();
            })
            .catch(error => {
                console.error('An error occurred:', error);
                hideLoadingModal();
                // hideLoader();
                // hideTypingIndicator();
            });
        }

        function showMessage(task) {
            alert('You clicked on ' + task);
        }

        function uploadFile() {
            var fileInput = document.getElementById('fileInput');
            var file = fileInput.files[0];

            if (file) {
                var fileName = file.name;
                var fileExtension = fileName.split('.').pop().toLowerCase();

                if (fileExtension === 'xls' || fileExtension === 'xlsx') {

                    // Create form data and append file
                    var formData = new FormData();
                    formData.append('file', file);
                    formData.append('message', 'done-task-file');
                    formData.append('task_id', currentTaskJson.task_id);
                    formData.append('class_id', currentTaskJson.class_id);
                    

                    displayMessage('Submitted file: ' + fileName, 'user');
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
                        // console.log(result);
                        // displayMessage(result, 'model');
                        // alert('File uploaded successfully');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('File upload failed');
                    });
                } else {
                    alert('Please upload an XLS or XLSX file');
                }
            } else {
                alert('No file selected');
            }
        }

        function validateFileType() {
            const taskType = document.getElementById('taskType').value;
            const fileInput = document.getElementById('taskFile');
            const file = fileInput.files[0];
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if ((taskType === 'PDF' && fileExtension !== 'pdf') || 
                (taskType === 'Excel' && (fileExtension !== 'xls' && fileExtension !== 'xlsx'))) {
                alert('Please upload a valid ' + taskType + ' file.');
                fileInput.value = ''; // Clear the input
            }

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
                // currentTaskFilePath = result.replace(/(\r\n|\n|\r)/gm, "").split(",")[0];
                // currentTaskFileUri = result.replace(/(\r\n|\n|\r)/gm, "").split(",")[1];

            })
            .catch(error => {
                console.error('Error:', error);
                // alert('File upload failed');
            });
        }

        //update chat ui based on current task task_type
        function updateChatUI() {
            if (currentTaskJson.task_type === 'PDF') {
                //hide file upload near chat message send button
                document.getElementById('fileInput').style.display = 'none';
            }
        }

        function validateCorrectFileType() {
            const taskType = document.getElementById('taskType').value;
            const fileInput = document.getElementById('correctTaskFile');
            const file = fileInput.files[0];
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if ((taskType === 'Excel' && (fileExtension !== 'xls' && fileExtension !== 'xlsx'))) {
                alert('Please upload a valid ' + taskType + ' file.');
                fileInput.value = ''; // Clear the input
            }

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
                alert(currentCorrectTaskFileUri);
                // currentTaskFilePath = result.replace(/(\r\n|\n|\r)/gm, "").split(",")[0];
                // currentTaskFileUri = result.replace(/(\r\n|\n|\r)/gm, "").split(",")[1];

            })
            .catch(error => {
                console.error('Error:', error);
                // alert('File upload failed');
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
            })
            .catch(error => {
                console.error('An error occurred:', error);
            });
        }

        function getTasksList(classId) {
            console.log('Fetching tasks for class ' + classId);
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=task-list&class_id=' + classId,
            })
            .then(response => response.json())
            .then(tasks => {
                const taskList = document.querySelector('.task-list');
                // taskList.innerHTML = '';
                tasks.forEach(task => {
                    console.log(task);
                    const newTaskItem = document.createElement('div');
                    newTaskItem.classList.add('task-item');
                    newTaskItem.id = 'task-' + task.task_id;
                    newTaskItem.textContent = task.task_name;
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

        function addTask() {
            document.getElementById('addTaskModal').classList.add('is-active');
        }


        function closeModal() {
            document.getElementById('addTaskModal').classList.remove('is-active');
            currentStep = 1;
            document.getElementById('step1').style.display = 'block';
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = 'none';
            document.getElementById('step4').style.display = 'none';
        }

        let currentStep = 1;
        updateFooterButtons(currentStep);

        const visibilities = {
            PDF: [1, 2, 3, 4],
            Excel: [1, 2, 4]
        };

        function toggleTaskTypeFields() {
            const taskType = document.getElementById('taskType').value;
            const pdfFields = document.querySelectorAll('.pdf-field');
            const excelFields = document.querySelectorAll('.excel-field');
            pdfFields.forEach(field => {
                field.style.display = taskType === 'PDF' ? 'block' : 'none';
            });
            excelFields.forEach(field => {
                field.style.display = taskType === 'Excel' ? 'block' : 'none';
            });
        }

        function nextStep() {
            const taskType = document.getElementById('taskType').value;
            const steps = visibilities[taskType];

            if (steps[currentStep - 1] === 1) {
                const fileInput = document.getElementById('taskFile');
                const fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';
                document.getElementById('displayTaskFile').innerText = fileName;
                document.getElementById('displayTaskFile2').innerText = fileName;
                document.getElementById('displayTaskName').innerText = document.getElementById('taskName').value;
                document.getElementById('displayTaskType').innerText = document.getElementById('taskType').value;
                document.getElementById('displayTaskDescription').innerText = document.getElementById('taskDescription').value;
            }

            if (steps[currentStep - 1] === 2) {
                if (taskType === 'PDF') {
                    document.getElementById('step3').style.display = 'block';
                    document.getElementById('step2').style.display = 'none';
                    currentStep++;
                    updateFooterButtons(currentStep);
                    return;
                }
            }

            if (steps[currentStep - 1] === 4) {
                document.getElementById('finalTaskName').innerText = document.getElementById('taskName').value;
                document.getElementById('finalTaskType').innerText = document.getElementById('taskType').value;
                document.getElementById('finalTaskDescription').innerText = document.getElementById('taskDescription').value;
                document.getElementById('finalTaskSummary').innerText = document.getElementById('taskSummary').value;
                document.getElementById('finalTaskQuestions').innerText = document.getElementById('taskQuestions').value;
                document.getElementById('step4').style.display = 'block';
                document.getElementById('step3').style.display = 'none';
                currentStep++;
                updateFooterButtons(currentStep);
                return;
            }

            document.getElementById('step' + steps[currentStep - 1]).style.display = 'none';
            currentStep++;
            document.getElementById('step' + steps[currentStep - 1]).style.display = 'block';
            updateFooterButtons(currentStep);
        }

        function previousStep() {
            const taskType = document.getElementById('taskType').value;
            const steps = visibilities[taskType];

            document.getElementById('step' + steps[currentStep - 1]).style.display = 'none';
            currentStep--;
            document.getElementById('step' + steps[currentStep - 1]).style.display = 'block';
            updateFooterButtons(currentStep);
        }

        function updateFooterButtons(step) {
            document.getElementById('prevButton').style.display = step > 1 ? 'inline' : 'none';
            document.getElementById('nextButton').style.display = step < 4 ? 'inline' : 'none';
            document.getElementById('saveButton').style.display = step === 4 ? 'inline' : 'none';
        }

        function saveTask() {
            const taskName = document.getElementById('taskName').value;
            const taskType = document.getElementById('taskType').value;
            const taskDescription = document.getElementById('taskDescription').value;
            const taskSummary = document.getElementById('taskSummary').value;
            const taskQuestions = document.getElementById('taskQuestions').value;

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=task-save&name=' + encodeURIComponent(taskName) + '&text=' + encodeURIComponent(taskDescription) + '&type=' + encodeURIComponent(taskType) + '&class_id=1' + '&file_clean=' + encodeURIComponent(currentTaskFilePath) + '&file_correct=' + encodeURIComponent(currentCorrectTaskFilePath) + '&file_uri=' + encodeURIComponent(currentTaskFileUri) + '&correct_file_uri=' + encodeURIComponent(currentCorrectTaskFileUri) + '&system_prompt=' + encodeURIComponent(taskSummary) + '&default_summary=' + encodeURIComponent(taskSummary) + '&default_self_check_questions=' + encodeURIComponent(taskQuestions),
            })

            // Add the new task to the task list
            const taskList = document.querySelector('.task-list');
            const newTaskItem = document.createElement('div');
            newTaskItem.classList.add('task-item');
            newTaskItem.textContent = taskName;
            newTaskItem.onclick = function() {
                reloadWithTaskId(taskList.children.length + 1);
            };
            taskList.appendChild(newTaskItem);

            // Close the modal
            closeModal();
        }


        // Initialize modal fields visibility
        toggleTaskTypeFields();

        function openClassModal() {
            document.getElementById('addClassModal').classList.add('is-active');
        }

        function closeClassModal() {
            document.getElementById('addClassModal').classList.remove('is-active');
        }

        function selectClass(className) {
            alert('You selected ' + className);
            closeClassModal();
        }

        userInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        });

        window.addEventListener("load", function() {
            setTimeout(function() {
                <?php //loadChatHistory(); ?>
                sendIntroMessage();
                console.log('Chat history loaded');
                getTasksList(1);
                getCurrentTask();
                console.log('currentTaskJson: ' + currentTaskJson);
            }, 2000); // 2000 milliseconds = 2 seconds
        });
        
    </script>
</body>
</html>
