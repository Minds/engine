<?php
namespace Minds\Core\Nostr;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\DID;
use Minds\Exceptions\ServerErrorException;

/**
 * We share the same private keys as we use for DID
 */
class Keys
{
    /** @var User */
    protected $user;

    public function __construct(protected ?DID\Keypairs\Manager $didKeypairsManager = null)
    {
        $this->didKeypairsManager ??= Di::_()->get('DID\Keypairs\Manager');
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
}
