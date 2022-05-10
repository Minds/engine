<?php

namespace Minds\Core\Security\TwoFactor\Store;

use JsonSerializable;
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
class TwoFactorSecret implements JsonSerializable
{
    use MagicAttributes;

    /** @var string $guid - guid of the user */
    private $guid;

    /** @var string $timestamp - timestamp of the secret */
    private $timestamp;

    /** @var string $secret - secret value */
    private $secret;

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): mixed
    {
        return [
            '_guid' => $this->getGuid(),
            'ts' => $this->getTimestamp(),
            'secret' => $this->getSecret()
        ];
    }
}
