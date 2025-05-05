<?php

class GeminiModelSwitcher {
    private $api_key;
    private $models_list = ["gemini-2.0-flash", "gemini-1.5-flash", "gemini-1.5-flash-8b", "gemini-2.0-pro-exp-02-05", "gemini-1.5-pro"];

    public function __construct($api_key = '') {
        $this->api_key = $api_key;
    }

    public function setApiKey($api_key) {
        $this->api_key = $api_key;
    }

    public function getApiKey(): string {
        return $this->api_key;
    }

    public function setModelsList($models) {
        if (is_array($models)) {
            $this->models_list = $models;
        }
    }

    public function getModelsList(): array {
        return $this->models_list;
    }

    public function makeRequest($data = []): array {
        $models = $this->getModelsList();
        $max_attempts = 10;
        $attempt = 0;
        $only_pro_models = false; // Set to true if you only want to use pro models
    
        while ($attempt < $max_attempts) {
            if ($only_pro_models) {
                $models = array_values(array_filter($models, fn($model) => strpos($model, 'pro') !== false));
            }
            $model = $models[$attempt % count($models)]; // Cycle through models
            $api_key = $this->getApiKey();
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
            $headers = [
                'Content-Type: application/json'
            ];
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1000);
    
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            error_log("Attempt: {$attempt}, Model: {$model}, HTTP Code: {$http_code}");
            error_log("Response: " . print_r($response, true));

            if (strpos($response, 'Unable to submit request because the input token count is') !== false) {
                $only_pro_models = true;
            }
            
            if ($http_code >= 200 && $http_code < 300) {
                return [
                    'response' => $response,
                    'status' => $http_code,
                    'error' => null
                ];
            } else {
                $attempt++;
                // Consider adding a delay here before the next attempt
                sleep(1); // Wait for 1 second before retrying
            }
        }
    
        return [
            'response' => null,
            'status' => $http_code ?? 0,
            'error' => "Gemini API request failed after {$max_attempts} attempts."
        ];
    }
}