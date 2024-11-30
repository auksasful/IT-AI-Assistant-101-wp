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

    public function getTextFromFile($filePath)
    {
        $pdf = $this->parser->parseFile($filePath);
        $text = $pdf->getText();
        return $text;
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


    function uploadFile($api_key, $input_file, $display_name) {
        $base_url = "https://generativelanguage.googleapis.com";
        $chunk_size = 8388608;  // 8 MiB
        $mime_type = mime_content_type($input_file);
        $num_bytes = filesize($input_file);

        error_log("Starting upload of '{$input_file}' to {$base_url}...");
        error_log("  MIME type: '{$mime_type}'");
        error_log("  Size: {$num_bytes} bytes");

        // Initial resumable request defining metadata.
        $ch = curl_init("{$base_url}/upload/v1beta/files?key={$api_key}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Goog-Upload-Protocol: resumable",
            "X-Goog-Upload-Command: start",
            "X-Goog-Upload-Header-Content-Length: {$num_bytes}",
            "X-Goog-Upload-Header-Content-Type: {$mime_type}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['file' => ['display_name' => $display_name]]));
        $response = curl_exec($ch);
        $headers = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        // Extract upload URL from response headers
        preg_match('/x-goog-upload-url:\s*(.*)\s*/i', substr($response, 0, $headers), $matches);
        $upload_url = trim($matches[1]);

        if (empty($upload_url)) {
            error_log("Failed initial resumable upload request.");
            return false;
        }

        // Upload the actual bytes.
        $num_chunks = ceil($num_bytes / $chunk_size);
        $tmp_chunk_file = tempnam(sys_get_temp_dir(), 'upload-chunk');
        for ($i = 1; $i <= $num_chunks; $i++) {
            $offset = $i - 1;
            $byte_offset = $offset * $chunk_size;

            // Read the actual bytes to the tmp file.
            $handle = fopen($input_file, "rb");
            fseek($handle, $byte_offset);
            $chunk = fread($handle, $chunk_size);
            fclose($handle);
            
            file_put_contents($tmp_chunk_file, $chunk);

            $num_chunk_bytes = strlen($chunk);
            $upload_command = "upload";
            if ($i === $num_chunks) {
                // For the final chunk, specify "finalize".
                $upload_command .= ", finalize";
            }

            error_log("  Uploading {$byte_offset} - " . ($byte_offset + $num_chunk_bytes) . " of {$num_bytes}...");

            $ch = curl_init($upload_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Length: {$num_chunk_bytes}",
                "X-Goog-Upload-Offset: {$byte_offset}",
                "X-Goog-Upload-Command: {$upload_command}"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($tmp_chunk_file));
            curl_exec($ch);
            curl_close($ch);
        }

        unlink($tmp_chunk_file);

        error_log("Upload complete!");
        return true;
    }

    function uploadAndDescribeFile($api_key, $input_file, $display_name) {
        $base_url = "https://generativelanguage.googleapis.com";
        $chunk_size = 8388608;  // 8 MiB
        $mime_type = mime_content_type($input_file);
        $num_bytes = filesize($input_file);

        error_log("Starting upload of '{$input_file}' to {$base_url}...");
        error_log("  MIME type: '{$mime_type}'");
        error_log("  Size: {$num_bytes} bytes");

        // Initial resumable request defining metadata.
        $ch = curl_init("{$base_url}/upload/v1beta/files?key={$api_key}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Goog-Upload-Protocol: resumable",
            "X-Goog-Upload-Command: start",
            "X-Goog-Upload-Header-Content-Length: {$num_bytes}",
            "X-Goog-Upload-Header-Content-Type: {$mime_type}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['file' => ['display_name' => $display_name]]));
        $response = curl_exec($ch);
        $headers = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        // Extract upload URL from response headers
        preg_match('/x-goog-upload-url:\s*(.*)\s*/i', substr($response, 0, $headers), $matches);
        $upload_url = trim($matches[1]);

        if (empty($upload_url)) {
            error_log("Failed initial resumable upload request.");
            return false;
        }

        // Upload the actual bytes.
        $num_chunks = ceil($num_bytes / $chunk_size);
        $tmp_chunk_file = tempnam(sys_get_temp_dir(), 'upload-chunk');
        for ($i = 1; $i <= $num_chunks; $i++) {
            $offset = $i - 1;
            $byte_offset = $offset * $chunk_size;

            // Read the actual bytes to the tmp file.
            $handle = fopen($input_file, "rb");
            fseek($handle, $byte_offset);
            $chunk = fread($handle, $chunk_size);
            fclose($handle);
            
            file_put_contents($tmp_chunk_file, $chunk);

            $num_chunk_bytes = strlen($chunk);
            $upload_command = "upload";
            if ($i === $num_chunks) {
                // For the final chunk, specify "finalize".
                $upload_command .= ", finalize";
            }

            error_log("  Uploading {$byte_offset} - " . ($byte_offset + $num_chunk_bytes) . " of {$num_bytes}...");

            $ch = curl_init($upload_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Length: {$num_chunk_bytes}",
                "X-Goog-Upload-Offset: {$byte_offset}",
                "X-Goog-Upload-Command: {$upload_command}"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($tmp_chunk_file));
            curl_exec($ch);
            curl_close($ch);
        }

        unlink($tmp_chunk_file);

        error_log("Upload complete!");

        // After the file upload, we would now use the Gemini API to generate content.
        $file_uri = $upload_url; // Use the URL obtained after upload.
        $this->analyzePdf($api_key, $file_uri);

        $genaiServiceUrl = "{$base_url}/v1beta/media:upload";
        $model = "models/gemini-1.5-flash:";
        $prompt = "Describe the image with a creative description";
        $contents = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        ['file_data' => ['file_uri' => $file_uri, 'mime_type' => $mime_type]]
                    ]
                ]
            ]
        ];

        // print_r('contents: ' . $contents);
        $json_contents = json_encode($contents, JSON_PRETTY_PRINT);
        print_r('JSON contents: ' . $json_contents);

        // $ch = curl_init($genaiServiceUrl);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     "Content-Type: application/json"
        // ]);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['model' => $model, 'requestBody' => $contents, 'auth' => ['apiKey' => $api_key]]));
        // $generateContentResponse = curl_exec($ch);
        // curl_close($ch);

        // error_log("Generated Content: " . $generateContentResponse);
        $headers = [
            'Content-Type: application/json',
        ];
        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$api_key");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($contents));

        $response = curl_exec($ch);
        curl_close($ch);

        $assistant_response = print_r(json_decode($response));
        error_log("Generated Content: " . $assistant_response);


        return true;
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

            // print_r( [
            //     'uri' => $responseData['file']['uri'] ?? null,
            //     'mimeType' => $mimeType,
            // ]);
            return $responseData['file']['uri'];
            // return $this->analyzePdf($api_key, $responseData['file']['uri'], $message);
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


    public function analyzePdf($api_key, $fileUri, $message)
    {
        $client = new Client();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$api_key}";
        $jsonData = [
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
    
    public function analyzeExcel($api_key, $fileUri1, $fileUri2, $message)
    {
        error_log("Analyzing Excel files...");
        error_log("  File 1: {$fileUri1}");
        error_log("  File 2: {$fileUri2}");
        $client = new Client();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$api_key}";
        $jsonData = [
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


}

// Example usage:
// $pdfReader = new PdfReader();
// $text = $pdfReader->getTextFromFile('path/to/your/document.pdf');
// echo $text;

?>
