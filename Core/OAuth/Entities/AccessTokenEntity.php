<?php
/**
 * Minds OAuth Access Token
 */
namespace Minds\Core\OAuth\Entities;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity implements AccessTokenEntityInterface
{
    use AccessTokenTrait, TokenEntityTrait, EntityTrait;

    /** @var string */
    protected $ip;

    /** @var int */
    protected $lastActive;

    /**
     * Set last active ts
     * @param int $lastActive
     * @return void
     */
    public function setLastActive(string $lastActive)
    {
        $this->lastActive = $lastActive;
    }

    /**
     * Get last active ts
     * @return int
     */
    public function getLastActive()
    {
        return $this->lastActive;
    }

    /**
     * Set the IP
     * @param string $ip
     * @return void
     */
    public function setIp(string $ip)
    {
        $this->ip = $ip;
    }

    /**
     * Get the ip
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
}
