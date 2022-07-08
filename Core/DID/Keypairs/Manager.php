<?php
namespace Minds\Core\DID\Keypairs;

use Minds\Entities\User;

class Manager
{
    public function __construct(protected ?Repository $repository = null)
    {
        $this->repository ??= new Repository();
    }

    /**
     * This creates the keypair
     * @param User user
     * @return DIDKeypair
     */
    public function createKeypair(User $user): DIDKeypair
    {
        $didKeypair = new DIDKeypair();
        $didKeypair->setUserGuid($user->getGuid())
            ->setKeypair(sodium_crypto_sign_keypair());

        return $didKeypair;
    }

    /**
     * Saves a keypair to storage
     * @param DIDKeypair $didKeypair
     * @return bool
     */
    public function add(DIDKeypair $didKeypair): bool
    {
        return $this->repository->add($didKeypair);
    }

    /**
     * @return DIDKeypair
     */
    public function getKeypair(User $user): ?DIDKeypair
    {
        return $this->repository->get($user->getGuid());
    }

    /**
     * @param DIDKeypair $didKeypair
     * @return string
     */
    public function getPublicKey(DIDKeypair $didKeypair): string
    {
        return sodium_crypto_sign_publickey($didKeypair->getKeypair());
    }

    /**
     * @param DIDKeypair $didKeypair
     * @return string
     */
    public function getPrivateKey(DIDKeypair $didKeypair): string
    {
        return sodium_crypto_sign_secretkey($didKeypair->getKeypair());
    }

    /**
     * A Secp256k1 compatible private key
     * @return string
     */
    public function getSecp256k1PrivateKey(User $user): string
    {
        $didKeypair = $this->getKeypair($user);

        if (!$didKeypair) {
            $didKeypair = $this->createKeypair($user);
            $this->add($didKeypair);
        }

        $privateKey = pack("H*", hash('sha256', $this->getPrivateKey($didKeypair)));
        return $privateKey;
    }

    /**
     * Prefixes base64 encoded key with 'm', which is the index for base64
     * @param string $key
     * @return string
     */
    public function getMultibase(string $key): string
    {
        return 'm' . base64_encode($key);
    }
}
