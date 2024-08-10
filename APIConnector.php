<?php
require 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class ApiConnector {
    private $api_key;
    private $secret_key;

    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->secret_key = get_secret_key(); // Retrieve the secret key from the database
        if (!$this->secret_key) {
            throw new Exception('JWT_SECRET_KEY environment variable is not set.');
        }
    }

    public function test_connection($username, $user_type, $register_request) {
        if ($register_request) {
            error_log('Testing connection for registration');
            // Register mode
            $ch = curl_init();
    
            curl_setopt($ch, CURLOPT_URL, 'https://api.naga.ac/v1/tokenizer');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["input" => "Test"]));
    
            $headers = [
                'Accept: application/json',
                'Authorization: Bearer ' . $this->api_key,
                'Content-Type: application/json'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
            $response = curl_exec($ch);
    
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
                return false;
            }
    
            $response_data = json_decode($response, true);

            if (isset($response_data['total']) && $response_data['total'] == 1) {
                return $this->generate_jwt($username, $user_type);
            } elseif (isset($response_data['error']['type']) && $response_data['error']['type'] == 'invalid_api_key') {
                return false;
            } else {
                echo 'Unexpected response: ' . $response;
                return false;
            }
    
            curl_close($ch);
        } 
        return $this->generate_jwt($username, $user_type);
    }
    
    private function generate_jwt($username, $user_type) {
        error_log('Generating JWT token for ' . $username . ' (' . $user_type . ')'); // Debug statement
        $payload = [
            'iss' => 'your_issuer', // Replace with your issuer
            'iat' => time(),
            'exp' => time() + (60 * 60), // Token valid for 1 hour
            'sub' => 'user_id', // Replace with the user ID or other identifier
            "data" => [
                "username" => $username,
                "user_type" => $user_type
            ]
        ];
    
        return JWT::encode($payload, $this->secret_key, 'HS256');
    }

    public function verify_jwt($jwt_token) {
        try {
            $decoded = JWT::decode($jwt_token, new Key($this->secret_key, 'HS256'));
            return $decoded;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
