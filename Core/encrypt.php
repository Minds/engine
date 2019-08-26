<?php
namespace Minds\Core;

/**
 * Minds Encryption
 */
class encrypt extends base
{
    private $key;

    public function __construct($key)
    {
        $this->setKey($key);
    }

    /**
     * Encrypts a plain-text string using Minds standards
     * @param  string $encrypt
     * @return string
     */
    public function encrypt($encrypt)
    {
        $encrypt = serialize($encrypt);
        $iv = \mcrypt_create_iv(\mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
        $key = pack('H*', $this->key);
        $mac = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));
        $passcrypt = \mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt . $mac, MCRYPT_MODE_CBC, $iv);
        $encoded = base64_encode($passcrypt) . '|' . base64_encode($iv);
        return $encoded;
    }

    /**
     * Decrypts an encrypted string using Minds standards
     * @param  string $decrypt
     * @return string
     */
    public function decrypt($decrypt)
    {
        $decrypt = explode('|', $decrypt);
        $decoded = base64_decode($decrypt[0], true);
        $iv = base64_decode($decrypt[1], true);
        if (strlen($iv)!==\mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)) {
            return false;
        }
        $key = pack('H*', $this->key);
        $decrypted = trim(\mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv));
        $mac = substr($decrypted, -64);
        $decrypted = substr($decrypted, 0, -64);
        $calcmac = hash_hmac('sha256', $decrypted, substr(bin2hex($key), -32));
        if ($calcmac !== $mac) {
            return false;
        }
        $decrypted = unserialize($decrypted);
        return $decrypted;
    }

    /**
     * Sets the ecryption key to be used when encrypting/decryption
     * @param string $key - a 32 byte hexadecimal string
     */
    public function setKey($key)
    {
        if (\ctype_xdigit($key) && strlen($key) === 64) {
            $this->key = $key;
        } else {
            trigger_error('Invalid key. Key must be a 32-byte (64 character) hexadecimal string.', E_USER_ERROR);
        }
    }
}
