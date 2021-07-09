<?php
/**
 * Minds OAuth ScopeRepository.
 */

namespace Minds\Core\OAuth\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Minds\Core\OAuth\Entities\ClientEntity;
use Minds\Core\OAuth\Entities\ScopeEntity;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier($scopeIdentifier)
    {
        $scopes = [
            'basic' => [
                'description' => 'Basic details about you',
            ],
            'email' => [
                'description' => 'Your email address',
            ],
            'checkout' => [
                'description' => 'Wyre transactions',
            ],
            'openid' => [
                'description' => 'OpenID connect scope'
            ],
        ];

        if (array_key_exists($scopeIdentifier, $scopes) === false) {
            return;
        }

        $scope = new ScopeEntity();
        $scope->setIdentifier($scopeIdentifier);

        return $scope;
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {
        if ($clientEntity instanceof ClientEntity) {
            foreach ($clientEntity->getScopes() as $scopeIdentifier) {
                $scope = new ScopeEntity();
                $scope->setIdentifier($scopeIdentifier);
                $scopes[] = $scope;
            }
        }

        return $scopes;
    }
}
