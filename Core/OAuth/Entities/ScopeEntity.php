<?php
/**
 * Minds OAuth Scope
 */
namespace Minds\Core\OAuth\Entities;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ScopeEntity implements ScopeEntityInterface
{
    use EntityTrait;

    public function jsonSerialize(): string
    {
        return $this->getIdentifier();
    }
}
