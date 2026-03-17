<?php
/**
 * Encryption Library for SMTP Passwords
 */

class MailSenderEncryption {
    // Secret Key should be stored somewhere very protected in a real production app.
    private $secretKey = "A_VERY_SECRET_KEY_CHANGEME_123456"; 
    private $method = "AES-256-CBC";

    public function encrypt($data) {
        $iv_length = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($data, $this->method, $this->secretKey, 0, $iv);
        return base64_encode($encrypted . "::" . $iv);
    }

    public function decrypt($data) {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, $this->method, $this->secretKey, 0, $iv);
    }
}

// Initial instance
$encryption = new MailSenderEncryption();
?>
