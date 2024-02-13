<?php
namespace Minds\Core\Payments\Stripe\Keys;

use Minds\Core\Security\Vault\VaultTransitService;
use Minds\Exceptions\UserErrorException;
use Stripe\StripeClient;

class StripeKeysService
{
    public function __construct(
        private StripeKeysRepository $repository,
        private VaultTransitService $vaultTransitService,
    ) {

    }

    /**
     * Returns the public key
     */
    public function getPubKey(): ?string
    {
        $keys = $this->repository->getKeys();
        if (!$keys) {
            return null;
        }

        list($pubKey, ) = $keys;
        return $pubKey;
    }

    /**
     * Returns the plaintext sec key
     */
    public function getSecKey(): ?string
    {
        list(, $secKeyCipherText) = $this->repository->getKeys();

        if (!$secKeyCipherText) {
            return null;
        }

        $secKeyPlainText = $this->vaultTransitService->decrypt($secKeyCipherText);

        return $secKeyPlainText;
    }

    /**
     * Sets the stripe keys, encrypts the sec key and stores to the repository
     */
    public function setKeys(string $pubKey, string $secKeyPlainText, bool $validate = true): bool
    {
        if ($validate) {
            // Before we set the keys, we will try and validate the keys
            try {
                $stripeClient = new StripeClient($secKeyPlainText);
                $stripeClient->checkout->sessions->all(['limit' => 3]);
            } catch (\Exception $e) {
                throw new UserErrorException($e->getMessage());
            }
        }

        $secKeyCipherText = $this->vaultTransitService->encrypt($secKeyPlainText);

        return $this->repository->setKeys(
            pubKey: $pubKey,
            secKeyCipherText: $secKeyCipherText,
        );
    }

}
