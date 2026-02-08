<?php

use App\Core\Libraries\Encrypt;
use Tests\TestCase;

class EncryptTest extends TestCase {
    
    public function test_encrypt_and_decrypt() {
        $original = "Secret message";
        $encrypted = Encrypt::encrypt($original);
        
        assert_not_equal($original, $encrypted);
        assert_true(strlen($encrypted) > 0);
        
        $decrypted = Encrypt::decrypt($encrypted);
        assert_equal($original, $decrypted);
    }

    public function test_encrypt_array() {
        $data = ['name' => 'John', 'role' => 'admin'];
        $encrypted = Encrypt::encrypt($data);
        $decrypted = Encrypt::decrypt($encrypted);
        
        assert_equal(json_encode($data), $decrypted);
    }

    public function test_hash_password() {
        $password = "mySecurePassword123";
        $hash = Encrypt::hashPassword($password);
        
        assert_not_equal($password, $hash);
        assert_true(strlen($hash) > 20); // bcrypt hash length
    }

    public function test_verify_password() {
        $password = "testPassword";
        $hash = Encrypt::hashPassword($password);
        
        assert_true(Encrypt::verifyPassword($password, $hash));
        assert_false(Encrypt::verifyPassword("wrongPassword", $hash));
    }

    public function test_generate_token() {
        $token1 = Encrypt::generateToken();
        $token2 = Encrypt::generateToken();
        
        assert_not_equal($token1, $token2);
        assert_equal(strlen($token1), 64); // 32 bytes = 64 hex chars
    }

    public function test_encrypt_decryption_with_special_chars() {
        $message = "Hello! @#$%^&*() 中文 العربية";
        $encrypted = Encrypt::encrypt($message);
        $decrypted = Encrypt::decrypt($encrypted);
        
        assert_equal($message, $decrypted);
    }
}

require_once __DIR__ . '/bootstrap.php';
$test = new EncryptTest();
$test->run();
