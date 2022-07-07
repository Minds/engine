<?php
namespace Minds\Core\Blockchain\Skale;

use kornrunner\Keccak;
use Minds\Core\Nostr\Keys as NostrKeys;
use Minds\Exceptions\ServerErrorException;

/**
 * Keys for SKALE - extends NostrKeys using the same private key.
 * Unlike Nostr, uncompressed public keys are needed for address generation.
 */
class Keys extends NostrKeys
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get private key as hex - prefixed with 0x.
     * @return ?string - private key in hex format.
     */
    public function getSecp256k1PrivateKeyAsHex(): ?string
    {
        $privateKey = bin2hex($this->getSecp256k1PrivateKey());
        if (stripos($privateKey, '0x') !== 0) {
            return "0x$privateKey";
        }
        return $privateKey;
    }

    /**
     * Get UNCOMPRESSED Secp256k1 public key.
     * Modified version of https://github.com/Bit-Wasp/secp256k1-php/blob/v0.2/examples/create_public_key.php
     * @return string uncompressed Secp256k1 public key.
     * @throws ServerErrorException - if unable to serialize key, or key is invalid.
     */
    public function getSecp256k1PublicKey(): string
    {
        $context = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);

        $privateKey = $this->getSecp256k1PrivateKey();
        
        $publicKey = null;
        $result = secp256k1_ec_pubkey_create($context, $publicKey, $privateKey);
        if ($result === 1) {
            $serializeFlags = SECP256K1_EC_UNCOMPRESSED;
        
            $serialized = '';
            if (1 !== secp256k1_ec_pubkey_serialize($context, $serialized, $publicKey, $serializeFlags)) {
                throw new ServerErrorException('secp256k1_ec_pubkey_serialize: failed to serialize public key');
            }
        
            return unpack("H*", $serialized)[1];
        } else {
            throw new ServerErrorException('secp256k1_pubkey_create: secret key was invalid');
        }
    }

    /**
     * Gets wallet address from Secp2561k public key by removing the prefix,
     * taking a keccak256 hash of the key and prepending 0x to the last 40 characters.
     * @return string - wallet address derived from private key.
     */
    public function getWalletAddress(): string
    {
        $publicKey = $this->getSecp256k1PublicKey();
        $publicKeyTrimmed = substr($publicKey, 2);

        $keccakHashedPublicKey = Keccak::hash(hex2bin($publicKeyTrimmed), 256);
        return '0x' . substr($keccakHashedPublicKey, -40);
    }
}
