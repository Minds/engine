<?php

namespace Minds\Core\Security\TwoFactor\Store;

use Minds\Traits\MagicAttributes;

/**
 * Object containing two-factor authentication secret and metadata.
 * @method string getGuid()
 * @method self setGuid(string $guid)
 * @method string getTimestamp()
 * @method self setTimestamp(string $timestamp)
 * @method string getSecret()
 * @method self setSecret(string $secret)
 */
class TwoFactorSecret
{
    use MagicAttributes;

    /** @var string $guid - guid of the user */
    private $guid;

    /** @var string $timestamp - timestamp of the secret */
    private $timestamp;

    /** @var string $secret - secret value */
    private $secret;

    /**
     * Instance held values as a JSON object ready for insertion into store.
     * @return void
     */
    public function toJson(): string
    {
        return json_encode([
            '_guid' => $this->getGuid(),
            'ts' => $this->getTimestamp(),
            'secret' => $this->getSecret()
        ]);
    }
}
