<?php

require_once 'vendor/autoload.php';

use Smalot\PdfParser\Parser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PdfReader
{
    private $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }


    public function getTextFromPages($filePath)
    {
        $pdf = $this->parser->parseFile($filePath);
        $pages = $pdf->getPages();
        $texts = [];

        foreach ($pages as $page) {
            $texts[] = $page->getText();
        }

        return $texts;
    }

    public function uploadFileNew($api_key, $filePath, $displayName, $contentType='application/pdf')
    {
        $client = new Client();
        error_log("Starting upload of '{$filePath}' to Google Cloud Storage...");
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

            return $responseData['file']['uri'];
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


    public function analyzePdf($api_key, $fileUri, $message, $system_prompt = "")
    {
        $client = new Client();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$api_key}";
        
        if (empty($system_prompt)) {
            $system_prompt = "You analyze the PDF and provide answer from it.";
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
                [
                    'role' => 'user',
                    'parts' => [
                        ['fileData' => ['fileUri' => $fileUri, 'mimeType' => 'application/pdf']],
                        [
                            'text' => $message,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 64,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $jsonData,
            ]);
    
            $responseData = json_decode($response->getBody()->getContents(), true);
    
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

    public function analyzePdfSelfCheck($api_key, $fileUri, $message, $system_prompt)
    {
        $file_questions = $this->analyzePdf($api_key, $fileUri, $message, $system_prompt);
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
        
        $client = new Client();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$api_key}";
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => 'List all the questions based on the schema given.'
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
                'topK' => 64,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        ];
    
        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $jsonData,
            ]);
    
            $responseData = json_decode($response->getBody()->getContents(), true);
    
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
        error_log("Analyzing Excel files...");
        error_log("  File 1: {$fileUri1}");
        error_log("  File 2: {$fileUri2}");
        $client = new Client();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$api_key}";
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => 'Compare the first file uploaded that is students and second that is the correct solution and try to make the asker understand the problem without exposing too much information about the final solution. Talk in Lithuanian.'
                    ],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['fileData' => ['fileUri' => $fileUri1, 'mimeType' => 'text/plain']],
                        ['fileData' => ['fileUri' => $fileUri2, 'mimeType' => 'text/plain']],
                        [
                            'text' => $message,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 64,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $jsonData,
            ]);
    
            $responseData = json_decode($response->getBody()->getContents(), true);            
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

    public function analyzeExcelQuestion($api_key, $fileUri, $fileUri2, $message, $fileUri3='')
    {
        error_log("Asking from Excel file...");
        error_log("  File 1: {$fileUri}");
        error_log("  File 2: {$fileUri2}");

        $client = new Client();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$api_key}";
        $jsonData = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => 'Compare the first file uploaded that is students and second that is the correct solution and try to make the asker understand the problem without exposing too much information about the final solution. Talk in Lithuanian.'
                    ],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['fileData' => ['fileUri' => $fileUri, 'mimeType' => 'text/plain']],
                        ['fileData' => ['fileUri' => $fileUri2, 'mimeType' => 'text/plain']],
                        [
                            'text' => $message,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 64,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];
    
        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $jsonData,
            ]);
    
            $responseData = json_decode($response->getBody()->getContents(), true);            
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


