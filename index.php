
<?php

session_start();
require_once 'APIConnector.php';
require_once 'ClassManager.php';
require_once 'UserManager.php';
require_once 'ExcelReader.php';
require_once 'TaskData.php';
require_once 'PDFReader.php';

$api_connector = new ApiConnector('');
// $classManager = new ClassManager();
$user_manager = new UserManager();
$current_task = new TaskData();

if(isset($_GET['task_id'])){
    $current_task = $current_task->getTaskData($_GET['task_id']);
}
else {
    $current_task = $current_task->getTaskData(1);
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
                "text" => $current_task->getSystemPrompt()
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
                    "text" => $current_task->getSystemPrompt()
                ]
            ],
            "contents" => []
        ];
    }

    if($data['messages'][count(value: $data['messages']) - 1]['content'] == "check-excel"){
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
        $folderPath = WP_CONTENT_DIR . '/ITAIAssistant101/' . $username . '/' . 'TASK1';
        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        $file = $_FILES['file'];
        $filePath = $folderPath . '/' . basename($file['name']);

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $excel_reader = new ExcelReader($filePath);

            $excel_data = $excel_reader->readDataWithCoordinates();
            $user_excel_string = print_r($excel_data, true);
            $response = processChatInput("check-excel");
            // echo $response;
            error_log('resultsss:' . print_r($excel_data, true) . ' ' . $response);
            return $response;
        } else {
            return 'File upload failed';
        }
    } else {
        return 'Invalid request method or no file uploaded';
    }
}

function call_embedded_pdf($user_message) {
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

    // merge passages shorter than 40 words
    $text_array = array_reduce($text_array, function($carry, $text) {
        if (empty($carry)) {
            $carry[] = $text;
        } else {
            $lastIndex = count($carry) - 1;
            if (str_word_count($text) < 40) {
                if (str_word_count($carry[$lastIndex]) < 40) {
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


// Main execution logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message'])) {
        $input = $_POST['message'];
        if ($input === 'intro-message') {
            sendModelMessage($current_task->getPrompts()[0]);
            sendModelMessage($current_task->getTaskFileClean());
            // sendModelFile($current_task->getTaskFileClean());
        } else {
            $response = processChatInput($input);
            echo $response;
        }
    } elseif (isset($_FILES['file'])) {
        $result = uploadFile();
        echo $result;
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
            <div class="class-selector">
                <div class="select">
                    <select>
                        <option>Select Class</option>
                        <option>Class 1</option>
                        <option>Class 2</option>
                        <option>Class 3</option>
                    </select>
                </div>
            </div>
            <div class="task-list">
                <div class="task-item" onclick="reloadWithTaskId(1)">Task 1</div>
                <div class="task-item" onclick="reloadWithTaskId(2)">Task 2</div>
                <div class="task-item" onclick="reloadWithTaskId(3)">Task 3</div>

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
    <script>
        const chatContainer = document.getElementById('chat-container');
        const userInput = document.getElementById('user-input');
        const loader = document.getElementById('loader');
        const typingIndicator = document.getElementById('typing-indicator');

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
            }, 2000); // 2000 milliseconds = 2 seconds
        });
        
    </script>
</body>
</html>
