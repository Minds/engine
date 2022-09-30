<?php
namespace Minds\Core\Nostr;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\DID;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;

/**
 * We share the same private keys as we use for DID
 */
class Keys
{
    /** @var User */
    protected $user;

    public function __construct(
        protected ?DID\Keypairs\Manager $didKeypairsManager = null,
        protected ?Repository $repository = null,
    ) {
        $this->didKeypairsManager ??= Di::_()->get('DID\Keypairs\Manager');
        $this->repository ??= new Repository();
    }

    /**
     * @param User $user
     * @return static
     */
    public function withUser(User $user): Keys
    {
        $instance = clone $this;
        $instance->user = $user;
        return $instance;
    }

    /**
     * A Secp256k1 compatible private key
     * @return string
     */
    public function getSecp256k1PrivateKey(): string
    {
        return $this->didKeypairsManager->getSecp256k1PrivateKey($this->user);
    }

    /**
     * A Secp256k1 compatible public key
     * @return string
     */
    public function getSecp256k1PublicKey(): string
    {
        $ctx = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);

        $keypair = null;

        secp256k1_keypair_create($ctx, $keypair, $this->getSecp256k1PrivateKey());

        $xonlyPub = null;
        $pkParity = null;
        $result = secp256k1_keypair_xonly_pub($ctx, $xonlyPub, $pkParity, $keypair);
        if (1 !== $result) {
            throw new ServerErrorException("Unable to get public key from secp256k1 keypair");
        }

        $xonlyPub32 = '';
        $result = secp256k1_xonly_pubkey_serialize($ctx, $xonlyPub32, $xonlyPub);
        if (1 !== $result) {
            throw new ServerErrorException("Unable to serialize xonlyPub32 from secp256k1 public key");
        }

        $publicKey = unpack("H*", $xonlyPub32)[1];
        return $publicKey;
    }

    /**
     * Saves the NIP26 delegation token
     * @param NIP26DelegateToken $nip26DelegateToken
     * @return bool
     */
    public function addNip26DelegationToken(NIP26DelegateToken $nip26DelegateToken): bool
    {
        /**
         * Verify logic
         */
        $ctx = secp256k1_context_create(SECP256K1_CONTEXT_VERIFY);
        secp256k1_xonly_pubkey_parse($ctx, $xonlyPubKey, pack('H*', $nip26DelegateToken->getDelegatorPublicKey()));
        $result = secp256k1_schnorrsig_verify(
            $ctx,
            pack('H*', $nip26DelegateToken->getSig()),
            pack('H*', $nip26DelegateToken->getSha256Token()),
            $xonlyPubKey
        );
        if (!$result) {
            throw new UserErrorException("Invalid signature for delegation token");
        }

        /**
         * Save to database
         */
        return $this->repository->addNip26DelegationToken($nip26DelegateToken);
    }

    /**
     * Returns (if setup) a NIP26 delegation token
     * @param string $delegatePublicKey
     * @return NIP26DelegateToken|null
     */
    public function getNip26DelegationToken(string $delegatePublicKey): ?NIP26DelegateToken
    {
        return $this->repository->getNip26DelegationToken($delegatePublicKey);
    }

    /**
    * Removes delegation
    * @param string $delegatePublicKey
    * @return bool
    */
    public function removeNip26DelegationToken(string $delegatePublicKey): bool
    {
        return $this->repository->deleteNip26DelegationToken($delegatePublicKey);
    }
}
