<?php
namespace Minds\Core;

/**
 * Minds Encryption
 */
class encrypt
{
    private $key;

    public function __construct($key)
    {
        $this->setKey($key);
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
