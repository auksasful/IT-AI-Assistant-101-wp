<?php

class ITAIAssistant_Data_Encryption {

	private $key;
	private $salt;

	public function __construct() {
		$this->key  = $this->get_default_key();
		$this->salt = $this->get_default_salt();
	}

	// encrypt and decrypt methods omitted for readability.

	private function get_default_key() {
		if ( defined( 'ITAIASSISTANT_ENCRYPTION_KEY' ) && '' !== ITAIASSISTANT_ENCRYPTION_KEY ) {
			return ITAIASSISTANT_ENCRYPTION_KEY;
		}

		if ( defined( 'LOGGED_IN_KEY' ) && '' !== LOGGED_IN_KEY ) {
			return LOGGED_IN_KEY;
		}

		// If this is reached, you're either not on a live site or have a serious security issue.
		return 'das-ist-kein-geheimer-schluessel';
	}

	private function get_default_salt() {
		if ( defined( 'ITAIASSISTANT_ENCRYPTION_SALT' ) && '' !== ITAIASSISTANT_ENCRYPTION_SALT ) {
			return ITAIASSISTANT_ENCRYPTION_SALT;
		}

		if ( defined( 'LOGGED_IN_SALT' ) && '' !== LOGGED_IN_SALT ) {
			return LOGGED_IN_SALT;
		}

		// If this is reached, you're either not on a live site or have a serious security issue.
		return 'das-ist-kein-geheimes-salz';
	}

    public function encrypt( $value ) {
        if ( ! extension_loaded( 'openssl' ) ) {
            return $value;
        }
    
        $method = 'aes-256-ctr';
        $ivlen  = openssl_cipher_iv_length( $method );
        $iv     = openssl_random_pseudo_bytes( $ivlen );
    
        $raw_value = openssl_encrypt( $value . $this->salt, $method, $this->key, 0, $iv );
        if ( ! $raw_value ) {
            return false;
        }
    
        return base64_encode( $iv . $raw_value );
    }
    
    public function decrypt( $raw_value ) {
        if ( ! extension_loaded( 'openssl' ) ) {
            return $raw_value;
        }
    
        $raw_value = base64_decode( $raw_value, true );
    
        $method = 'aes-256-ctr';
        $ivlen  = openssl_cipher_iv_length( $method );
        $iv     = substr( $raw_value, 0, $ivlen );
    
        $raw_value = substr( $raw_value, $ivlen );
    
        $value = openssl_decrypt( $raw_value, $method, $this->key, 0, $iv );
        if ( ! $value || substr( $value, - strlen( $this->salt ) ) !== $this->salt ) {
            return false;
        }
    
        return substr( $value, 0, - strlen( $this->salt ) );
    }
}

?>