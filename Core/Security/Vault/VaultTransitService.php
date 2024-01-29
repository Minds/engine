<?php
namespace Minds\Core\Security\Vault;

use GuzzleHttp\Exception\ClientException;
use Minds\Core\Config\Config;

/**
 * Provides transit encryption services
 */
class VaultTransitService
{
    public function __construct(
        private Client $client,
        private Config $config,
    ) {

    }

    /**
     * Encrypt a string using the transit service.
     * Returns cipher text
     */
    public function encrypt(string $plainText): string
    {
        try {
            $response = $this->client->request('POST', '/transit/encrypt/' . $this->getKeyId(), [
                'plaintext' => base64_encode($plainText),
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['data']['ciphertext'];
        } catch (ClientException $e) {
            if ((int) $e->getCode() === 404) {
                // The key doesn't exist,  create one
                $this->createKey();

                return $this->encrypt($plainText);
            }
            throw $e;
        }
    }

    /**
     * Decrypt cipher text using the transit service.
     * Returns plain text
     */
    public function decrypt(string $cipherText): string
    {
        try {
            $response = $this->client->request('POST', '/transit/decrypt/' . $this->getKeyId(), [
                'ciphertext' => $cipherText,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return base64_decode($body['data']['plaintext'], true);
        } catch (ClientException $e) {
            throw $e;
        }
    }

    /**
     * Creates the transit key (call once)
     */
    private function createKey(): bool
    {
        try {
            $this->client->request('POST', '/transit/keys/' . $this->getKeyId(), [
                'auto_rotate_period' => '8760h',
            ]);
            return true;
        } catch (ClientException $e) {
            throw $e;
        }
    }

    /**
     * A key will exist per tenant
     */
    private function getKeyId(): string
    {
        return "tenant-" . ($this->config->get('tenant_id') ?: -1);
    }

}
