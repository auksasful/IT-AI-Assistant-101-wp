<?php
require_once 'vendor/autoload.php';
require_once 'ClassManager.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class ApiConnector {
    private $api_key;
    private $secret_key;
    private $class_manager;

    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->secret_key = get_secret_key(); // Retrieve the secret key from the database
        if (!$this->secret_key) {
            throw new Exception('JWT_SECRET_KEY environment variable is not set.');
        }
        $this->class_manager = new ClassManager();
    }

    public function test_connection($username, $user_type, $register_request) {
        if ($register_request) {
            error_log('Testing connection for registration');
            // Register mode
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $this->api_key);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
                return false;
            }
            
            $response_data = json_decode($response, true);
            
            if (isset($response_data['models'])) {
                $this->class_manager->insert_class($username . "'s class", $username, true);
                return $this->generate_jwt($username, $user_type);
            } elseif (isset($response_data['error']['message']) && strpos($response_data['error']['message'], 'API key') !== false) {
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
            'exp' => time() + 60 * 60 * 24 * 30, // Token valid for 1 month
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

    public function get_class_api_key($class_id) {
        return $this->class_manager->get_class_API_key($class_id);
    }
}
?>
