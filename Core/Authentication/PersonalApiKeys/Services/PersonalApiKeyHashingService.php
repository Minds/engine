<?php
namespace Minds\Core\Authentication\PersonalApiKeys\Services;

use Minds\Core\Config\Config;

class PersonalApiKeyHashingService
{
    public function __construct(
        private Config $config,
    ) {
    }

    /**
     * Creates a random, high entropy, secret that will be returned to the user
     */
    public function generateSecret(): string
    {
        return 'pak_' . hash('sha256', openssl_random_pseudo_bytes(512));
    }

    /**
     * Hash the secret against our private key
     * (We use the same key as we use for sessions).
     */
    public function hashSecret(string $secret): string
    {
        $secret = substr($secret, 4);
        return hash_hmac('sha512', $secret, $this->getHmacKey());
    }

    /**
     * Returns the key that will hash the secrets.
     * This is, for now, the same key as we use for sessions
     */
    private function getHmacKey(): string
    {
        return file_get_contents($this->config->get('sessions')['private_key']);
    }

}
