<?php
/**
 * Minds OAuth Client
 */
namespace Minds\Core\OAuth\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
    use EntityTrait, ClientTrait;

    /** @var array */
    protected $scopes = [];

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function setRedirectUri($uri)
    {
        $this->redirectUri = $uri;
    }

    /**
     * Sets the scopes that a client can user
     * @param array $scopes
     * @return void
     */
    public function setScopes(array $scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * Returns the scopes that a client can use
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }
}
