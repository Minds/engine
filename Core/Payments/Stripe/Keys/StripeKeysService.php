<?php
namespace Minds\Core\Payments\Stripe\Keys;

use Stripe\StripeClient;
use Minds\Core\Security\Vault\VaultTransitService;
use Minds\Exceptions\UserErrorException;

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
    public function getPubKey(): string
    {
        list($pubKey, ) = $this->repository->getKeys();
        return $pubKey;
    }

    /**
     * Returns the plaintext sec key
     */
    public function getSecKey(): string
    {
        list(, $secKeyCipherText) = $this->repository->getKeys();

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
