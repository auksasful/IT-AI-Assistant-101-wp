<?php

require_once 'vendor/autoload.php';
require_once 'languageconfig.php';
require_once 'GeminiModelSwitcher.php';

use Smalot\PdfParser\Parser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GeminiManager
{
    private $parser;
    private $task_id;
    private $class_id;
    private $user_username;
    private $modelSwitcher;

    public function __construct($task_id, $class_id, $user_username)
    {
        $this->parser = new Parser();
        $this->task_id = $task_id;
        $this->class_id = $class_id;
        $this->user_username = $user_username;
        $this->modelSwitcher = new GeminiModelSwitcher();
    }

    public function uploadFileNew($api_key, $filePath, $displayName, $contentType='application/pdf')
    {
        $client = new Client();
        $url = "https://generativelanguage.googleapis.com/upload/v1beta/files?key={$api_key}";
        $fileContent = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath);
        $numBytes = strlen($fileContent);

        try {
            $response = $client->post($url, [
                'headers' => [
                    'X-Goog-Upload-Command' => 'start, upload, finalize',
                    'X-Goog-Upload-Header-Content-Length' => $numBytes,
                    'X-Goog-Upload-Header-Content-Type' => $mimeType,
                    'Content-Type' => $contentType,
                ],
                'query' => ['uploadType' => 'media', 'key' => $api_key],
                'body' => $fileContent,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            return [$responseData['file']['uri'], $responseData['file']['mimeType']];
        } catch (RequestException $e) {
            throw $e;
        }
    }

    public function fileExists($api_key, $fileUri)
    {
        $client = new Client();
        $url = "{$fileUri}?key={$api_key}";

        try {
            $response = $client->get($url);
            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            return false;
        }
    }


    public function analyzePdf($api_key, $fileUri, $message, $system_prompt = "", $saveToChatHistory = true)
    {
        global $prompts;

        $taskManager = new TaskManager();
        $chatHistory = $taskManager->get_student_task_chat_history($this->task_id, $this->class_id, $this->user_username);

        $chatHistoryArray = [];
        foreach ($chatHistory as $chat) {
            $chatHistoryArray[] = [
                'role' => $chat->message_role,
                'parts' => [
                    [
                        'text' => $chat->user_message,
                    ],
                ],
            ];
        }

        $chatHistoryArray[] = [
            'role' => 'user',
            'parts' => [
                ['fileData' => ['fileUri' => $fileUri, 'mimeType' => 'application/pdf']],
                [
                    'text' => $message,
                ],
            ],
        ];


        $this->modelSwitcher->setApiKey($api_key);
        
        if (empty($system_prompt)) {
            $system_prompt = $prompts['analyze_pdf_system_prompt'];
        }
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $system_prompt
                    ],
                ],
            ],
            'contents' => [
                $chatHistoryArray
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $this->modelSwitcher->makeRequest($jsonData);
    
            $responseData = json_decode($response['response'], true);
    
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                // Return only the text part
                $text = $responseData['candidates'][0]['content']['parts'][0]['text'];
                if ($saveToChatHistory) {
                    $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'user',  $system_prompt,  $message);
                    $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'model',  $system_prompt,  $text);
                }
                return $text;
            } else {
                throw new Exception("Text content not found in the response");
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }  

    public function analyzePdfSelfCheck($api_key, $fileUri, $message, $system_prompt)
    {
        global $prompts;
        $file_questions = $this->analyzePdf($api_key, $fileUri, $message, $system_prompt, saveToChatHistory: false);
        $schema = [
            'description' => 'List of questions and answers',
            'type' => 'object',
            'properties' => [
                'questions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'answer' => [
                                'type' => 'string',
                                'description' => 'Answer text',
                                'nullable' => false,
                            ],
                            'order_number' => [
                                'type' => 'number',
                                'description' => 'Order number of the question',
                                'nullable' => false,
                            ],
                            'question' => [
                                'type' => 'string',
                                'description' => 'Question text',
                                'nullable' => false,
                            ],
                        ],
                        'required' => ['answer', 'order_number', 'question'],
                    ],
                ],
            ],
            'required' => ['questions'],
        ];
        
        $this->modelSwitcher->setApiKey($api_key);
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $prompts['schema_prompt']
                    ],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $file_questions,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        ];
    
        try {
    
            $response = $this->modelSwitcher->makeRequest($jsonData);
    
            $responseData = json_decode($response['response'], true);
    
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                // Return only the text part
                return $responseData['candidates'][0]['content']['parts'][0]['text'];
            } else {
                throw new Exception("Text content not found in the response");
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }  
    
    public function analyzeExcel($api_key, $fileUri1, $fileUri2, $message)
    {
        global $lang;
        $taskManager = new TaskManager();
        $chatHistory = $taskManager->get_student_task_chat_history($this->task_id, $this->class_id, $this->user_username);

        $chatHistoryArray = [];
        foreach ($chatHistory as $chat) {
            $chatHistoryArray[] = [
                'role' => $chat->message_role,
                'parts' => [
                    [
                        'text' => $chat->user_message,
                    ],
                ],
            ];
        }

        $chatHistoryArray[] = [
            'role' => 'user',
            'parts' => [
                ['fileData' => ['fileUri' => $fileUri1, 'mimeType' => 'text/plain']],
                ['fileData' => ['fileUri' => $fileUri2, 'mimeType' => 'text/plain']],
                [
                    'text' => $message,
                ],
            ],
        ];

        $system_prompt = 'Compare the first file uploaded that is students and second that is the correct solution and try to make the asker understand the problem without exposing too much information about the final solution. Talk in Lithuanian.';

        $this->modelSwitcher->setApiKey($api_key);
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $system_prompt
                    ],
                ],
            ],
            'contents' => [
                $chatHistoryArray
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $this->modelSwitcher->makeRequest($jsonData);
    
            $responseData = json_decode($response['response'], true);
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                // Return only the text part
                $text = $responseData['candidates'][0]['content']['parts'][0]['text'];
                // make user's message just $lang['added_excel_file]
                $userMessage = $lang['added_excel_file'];
                $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'user',  $system_prompt,  $userMessage);
                $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'model',  $system_prompt,  $text);
                return $text;
            } else {
                throw new Exception("Text content not found in the response");
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function analyzeExcelQuestion($api_key, $fileUri, $fileUri2, $message, $fileUri3='')
    {
        global $prompts;
        $taskManager = new TaskManager();
        $chatHistory = $taskManager->get_student_task_chat_history($this->task_id, $this->class_id, $this->user_username);

        $chatHistoryArray = [];
        foreach ($chatHistory as $chat) {
            $chatHistoryArray[] = [
                'role' => $chat->message_role,
                'parts' => [
                    [
                        'text' => $chat->user_message,
                    ],
                ],
            ];
        }

        $chatHistoryArray[] = [
            'role' => 'user',
            'parts' => [
                ['fileData' => ['fileUri' => $fileUri, 'mimeType' => 'text/plain']],
                ['fileData' => ['fileUri' => $fileUri2, 'mimeType' => 'text/plain']],
                [
                    'text' => $message,
                ],
            ],
        ];

        $system_prompt = $prompts['analyze_excel_system_prompt'];


        $this->modelSwitcher->setApiKey($api_key);
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $system_prompt
                    ],
                ],
            ],
            'contents' => [
                $chatHistoryArray
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $this->modelSwitcher->makeRequest($jsonData);
    
            $responseData = json_decode($response['response'], true);

            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $responseData['candidates'][0]['content']['parts'][0]['text'];
                $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'user',  $system_prompt,  $message);
                $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'model',  $system_prompt,  $text);
                // Return only the text part
                return $text;
            } else {
                throw new Exception("Text content not found in the response");
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function analyzePython($api_key, $data_file_path='', $wanted_result, $message)
    {
        global $prompts;
        $taskManager = new TaskManager();
        $chatHistory = $taskManager->get_student_task_chat_history($this->task_id, $this->class_id, $this->user_username);
        $data = '';
        if (file_exists($data_file_path)) {
            $data = file_get_contents($data_file_path);
        }

        $system_prompt = $data ? 
            "{$prompts['analyze_python_prompt_1']}\n{$data}\n    
             {$prompts['analyze_python_prompt_2']} {$wanted_result};\n    
             {$prompts['analyze_python_prompt_3']}" : 
            "{$prompts['analyze_python_prompt_4']} {$wanted_result};\n    
             {$prompts['analyze_python_prompt_5']}";


        //loop through the chat history
        $chatHistoryArray = [];
        foreach ($chatHistory as $chat) {
            $chatHistoryArray[] = [
                'role' => $chat->message_role,
                'parts' => [
                    [
                        'text' => $chat->user_message,
                    ],
                ],
            ];
        }

        $chatHistoryArray[] = [
            'role' => 'user',
            'parts' => [
                [
                    'text' => $message,
                ],
            ],
        ];

        $this->modelSwitcher->setApiKey($api_key);
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $system_prompt
                    ],
            ]
            ],
            'tools' => [['code_execution' => new stdClass()]],
            'contents' => [
                $chatHistoryArray
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $this->modelSwitcher->makeRequest($jsonData);
    
            $responseData = json_decode($response['response'], true);
            $text = "";

            if (isset($responseData['candidates'][0]['content']['parts'])) {
                foreach ($responseData['candidates'][0]['content']['parts'] as $part) {
                    if (!empty($part['text'])) {
                        $text .= $part['text'];
                    }
                }
            
                if (!empty($text)) {
                    $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'user',  $system_prompt,  $message);
                    $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'model',  $system_prompt,  $text);
                    return $text; // Return the concatenated text
                } else {
                    throw new Exception("Text content not found in the response");
                }
            } else {
                throw new Exception("Text parts not found in the response");
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function analyzePythonQuestion($api_key, $message)
    {
        global $prompts;

        $taskManager = new TaskManager();
        $chatHistory = $taskManager->get_student_task_chat_history($this->task_id, $this->class_id, $this->user_username);

        $taskData = $taskManager->get_task($this->task_id, $this->user_username);

        // put $taskData->task_name, $taskData->task_text, $taskData->python_program_execution_result into one string variable
        $task_text = $taskData->task_name . "\n" . $taskData->task_text . "\n" . $taskData->python_program_execution_result;


        $chatHistoryArray = [];
        $chatHistoryArray[] = [
            'role' => 'model',
            'parts' => [
                [
                    'text' => $task_text,
                ],
            ],
        ];
        foreach ($chatHistory as $chat) {
            $chatHistoryArray[] = [
                'role' => $chat->message_role,
                'parts' => [
                    [
                        'text' => $chat->user_message,
                    ],
                ],
            ];
        }

        $chatHistoryArray[] = [
            'role' => 'user',
            'parts' => [
                [
                    'text' => $message,
                ],
            ],
        ];

        $system_prompt = $prompts['analyze_python_question_prompt'];

        $this->modelSwitcher->setApiKey($api_key);
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $system_prompt
                    ],
                ],
            ],
            'contents' => [
                $chatHistoryArray
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $this->modelSwitcher->makeRequest($jsonData);
    
            $responseData = json_decode($response['response'], true);
    
            // $responseData = json_decode($response->getBody()->getContents(), true);            
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                // Return only the text part
                $text = $responseData['candidates'][0]['content']['parts'][0]['text'];
                $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'user',  $system_prompt,  $message);
                $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'model',  $system_prompt,  $text);

                return $text;
            } else {
                throw new Exception("Text content not found in the response");
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function analyzeOrange($api_key, $image_file_uri='', $image_file_mime_type='', $message)
    {
        global $prompts;


        $this->modelSwitcher->setApiKey($api_key);
        $jsonData = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['fileData' => ['fileUri' => $image_file_uri, 'mimeType' => $image_file_mime_type]],
                        [
                            'text' => $message,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $this->modelSwitcher->makeRequest($jsonData);
    
            $responseData = json_decode($response['response'], true);
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                // Return only the text part
                $message = $responseData['candidates'][0]['content']['parts'][0]['text'];
                echo $message;
                return $this->analyzeUrlEmbeddingsQuestion($api_key, '', '', $message, "Orange", true);
            } else {
                throw new Exception("Text content not found in the response");
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function analyzeOrangeQuestion($api_key, $message)
    {
        global $prompts;

        $taskManager = new TaskManager();
        $chatHistory = $taskManager->get_student_task_chat_history($this->task_id, $this->class_id, $this->user_username);

        $taskData = $taskManager->get_task($this->task_id, $this->user_username);

        $task_text = $taskData->task_name . "\n" . $taskData->task_text . "\n" . $taskData->python_program_execution_result;

        $chatHistoryArray = [];
        $chatHistoryArray[] = [
            'role' => 'model',
            'parts' => [
                [
                    'text' => $task_text,
                ],
            ],
        ];
        foreach ($chatHistory as $chat) {
            $chatHistoryArray[] = [
                'role' => $chat->message_role,
                'parts' => [
                    [
                        'text' => $chat->user_message,
                    ],
                ],
            ];
        }

        $chatHistoryArray[] = [
            'role' => 'user',
            'parts' => [
                [
                    'text' => $message,
                ],
            ],
        ];

        $system_prompt = $prompts['analyze_orange_question_prompt'];

        $this->modelSwitcher->setApiKey($api_key);
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $system_prompt
                    ],
                ],
            ],
            'contents' => [
                $chatHistoryArray
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $this->modelSwitcher->makeRequest($jsonData);
    
            $responseData = json_decode($response['response'], true);
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                // Return only the text part
                $text = $responseData['candidates'][0]['content']['parts'][0]['text'];
                $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'user',  $system_prompt,  $message);
                $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'model',  $system_prompt,  $text);

                return $text;
            } else {
                throw new Exception("Text content not found in the response");
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function analyzeUrlEmbeddingsQuestion($api_key, $data_file_path='', $wanted_result, $message, $task_type="Orange", $hasQuestion=false)
    {
        global $prompts;
        global $lang;
        $taskManager = new TaskManager();

        $docsUrls = null;
        if ($task_type == "Orange") {
            $docsUrlsFilePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/orange_docs_urls.json';
            $embeddingsFilePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/orange_embeddings.json';
        }
        elseif ($task_type == "Python") {
            $docsUrlsFilePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/python_docs_urls.json';
            $embeddingsFilePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/python_embeddings.json';
        }
        elseif ($task_type == "Excel") {
            $docsUrlsFilePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/excel_docs_urls.json';
            $embeddingsFilePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/excel_embeddings.json';
        }
        else {
            $docsUrlsFilePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/docs_urls.json';
            $embeddingsFilePath = WP_CONTENT_DIR . '/ITAIAssistant101/default_student_tasks/embeddings.json';
        }

        if (file_exists($docsUrlsFilePath)) { 
            // echo "Loading URLs from $docsUrlsFilePath\n";
            $docsUrls = json_decode(file_get_contents($docsUrlsFilePath), true); 
        }
        $embeddings = null;
        $json_content = file_get_contents($embeddingsFilePath);
        $data = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo 'JSON decode error: ' . json_last_error_msg();
            exit;
        }

        // Extract embeddings, URLs, and texts
        $embeddings = $data['embeddings'];               // Now $embeddings is an array of arrays
        $urls_with_embeddings = $data['urls_with_embeddings'];
        $texts = $data['texts'];


        $this->modelSwitcher->setApiKey($api_key);
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => 'You must translate this into English. You must return just the translated English text.'
                    ],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $message,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $this->modelSwitcher->makeRequest($jsonData);
    
            $responseData = json_decode($response['response'], true);
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                
                $english_prompt = $responseData['candidates'][0]['content']['parts'][0]['text'];
                $query_embedding = $this->embed_for_search($english_prompt);

                $embeddings = array_map(function($row) {
                    return array_map('floatval', $row);
                }, $embeddings);
                
                $query_embedding = array_map('floatval', $query_embedding);

                // Compute norms of embeddings
                $embeddings_norms = array_map([$this, 'l2_norm'], $embeddings);

                // Compute norm of query_embedding
                $query_norm = $this->l2_norm($query_embedding);

                // Normalize embeddings
                $embeddings_normalized = [];
                foreach ($embeddings as $i => $vector) {
                    $norm = $embeddings_norms[$i];
                    if ($norm == 0) {
                        // Handle zero norm to avoid division by zero
                        $normalized_vector = array_fill(0, count($vector), 0.0);
                    } else {
                        $normalized_vector = array_map(function($value) use ($norm) {
                            return $value / $norm;
                        }, $vector);
                    }
                    $embeddings_normalized[] = $normalized_vector;
                }

                // Normalize query_embedding
                if ($query_norm == 0) {
                    // Handle zero norm
                    $query_embedding_normalized = array_fill(0, count($query_embedding), 0.0);
                } else {
                    $query_embedding_normalized = array_map(function($value) use ($query_norm) {
                        return $value / $query_norm;
                    }, $query_embedding);
                }
                // Compute cosine similarities
                $cosine_similarities = [];
                foreach ($embeddings_normalized as $normalized_vector) {
                    $similarity = $this->dot_product($normalized_vector, $query_embedding_normalized);
                    $cosine_similarities[] = $similarity;
                }

                // Create an array of (index, similarity)
                $indexed_similarities = [];
                foreach ($cosine_similarities as $index => $similarity) {
                    $indexed_similarities[] = ['index' => $index, 'similarity' => $similarity];
                }

                // Sort in descending order of similarity
                usort($indexed_similarities, function($a, $b) {
                    return $b['similarity'] <=> $a['similarity'];
                });

                // Get the indices of the top 5 most similar documents
                $unique_similarities = [];
                $top_indices_with_similarities = [];

                foreach ($indexed_similarities as $item) {
                    if (!in_array($item['similarity'], $unique_similarities)) {
                        $unique_similarities[] = $item['similarity'];
                        $top_indices_with_similarities[] = $item;
                    }
                    if (count($top_indices_with_similarities) >= 5) {
                        break;
                    }
                }

                $top_indices = array_column($top_indices_with_similarities, 'index');

                // return "Number of top indices: " . count($top_indices) . "\n";

                // Print the top 5 URLs with their similarity scores and text snippets
                $text_content_to_ask = "";
                $url_texts = "";
                $urls = [];
                $similarities = [];
                // echo "Top 5 most similar URLs:\n";
                foreach ($top_indices as $idx) {
                    $url = $urls_with_embeddings[$idx];
                    $similarity = $cosine_similarities[$idx];
                    $urls[] = $url;
                    $similarities[] = $similarity;
                    $text_content = $texts[$idx];  // Retrieve the corresponding text content
                    $text_content_to_ask .= $text_content . "\n";
                    
                    // Fetch the content of the URL
                    $url_content = file_get_contents($url);
                    // Remove HTML tags
                    $plain_text = strip_tags($url_content);
                    // Append to $url_texts
                    $url_texts .= $plain_text . "\n";
                }

                // decode HTML entities first
                $url_texts = html_entity_decode($url_texts, ENT_QUOTES, 'UTF-8');
                
                $tempFilePath = tempnam(sys_get_temp_dir(), 'url_texts_') . '.txt';
                if (file_put_contents($tempFilePath, $url_texts) === false) {
                    throw new Exception("Failed to write temporary file");
                }

                $tempFileUri = $this->uploadFileNew($api_key, $tempFilePath, 'url_texts_.txt', 'text/plain')[0];
                $system_prompt = "You must answer the question using the text and some general knowledge. Do not mention where you got the answer. Speak in Lithuanian!";
                if ($hasQuestion) {
                    $answer = $this->analyzeTxt($api_key, $tempFileUri, $english_prompt , $system_prompt);
                } else {
                    $answer = $message;
                }
                $answer .= "\n<table class='table is-fullwidth'>";
                for($i = 0; $i < count($urls); $i++) {

                    // Print the URL and similarity score
                    $answer .= "<tr><td> " . $urls[$i] . " </td><td> " . $lang['cosine_similarity'] . " " . number_format($similarities[$i], 4) . " </td></tr>";
                }
                $answer .= "</table>";
                $taskManager->insert_student_task_chat_history($this->task_id,$this->class_id, $this->user_username, 'model',  $system_prompt,  $answer);
                echo $answer;


                return $answer;


            } else {
                throw new Exception("Text content not found in the response");
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Function to compute L2 norm of a vector
    function l2_norm($vector) {
        $sum = 0.0;
        foreach ($vector as $value) {
            $sum += $value * $value;
        }
        return sqrt($sum);
    }

    // Function to compute dot product of two vectors
    function dot_product($v1, $v2) {
        $result = 0.0;
        $count = count($v1);
        for ($i = 0; $i < $count; $i++) {
            $result += $v1[$i] * $v2[$i];
        }
        return $result;
    }

    function embed_for_search($text) {
        global $API_KEY;
        $url = "https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key=$API_KEY";
        
        // Extract the first 5 words for the title
        
        $data = array(
          'model' => 'models/text-embedding-004',
          'content' => array(
            'parts' => array(
              array(
                'text' => $text //,
              ),
            ),
          ),
          'task_type' => 'semantic_similarity',
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

    public function analyzeTxt($api_key, $fileUri, $message, $system_prompt = "")
    {
        global $prompts;
        $this->modelSwitcher->setApiKey($api_key);

        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => $system_prompt
                    ],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['fileData' => ['fileUri' => $fileUri, 'mimeType' => 'text/plain']],
                        [
                            'text' => $message,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];

        try {
            $response = $this->modelSwitcher->makeRequest($jsonData);
    
            $responseData = json_decode($response['response'], true);
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                // Return only the text part
                return $responseData['candidates'][0]['content']['parts'][0]['text'];
            } else {
                throw new Exception("Text content not found in the response");
            }
        } catch (RequestException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }


}


