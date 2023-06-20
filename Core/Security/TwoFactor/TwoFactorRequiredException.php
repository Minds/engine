<?php
namespace Minds\Core\Security\TwoFactor;

use Minds\Exceptions\UserErrorException;

class TwoFactorRequiredException extends UserErrorException
{
    /** @var int */
    protected $code = 401;

    /** @var string */
    protected $message = "TwoFactor is required.";

    /** @var string 2fa key to be passed back with code submission and subsequent 2fa sending requests */
    protected $key = '';

    /**
     * Get 2fa key.
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set 2fa key.
     * @param string key - 2fa key
     * @return self;
     */
    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }
}
