<?php
/**
 * Minds Access Token Manager
 */

namespace Minds\Core\OAuth\Managers;

use Minds\Core;
use Minds\Core\OAuth\Entities\AccessTokenEntity;
use Minds\Core\OAuth\Repositories\AccessTokenRepository as Repository;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

class AccessTokenManager
{
    /** @var Repository $repository */
    private $repository;

    public function __construct(
        $repository = null
    ) {
        $this->repository = $repository ?: new Repository;
    }

    /**
     * Return all OAuth access tokens
     * @param User $user
     * @return array
     */
    public function getList(User $user): array
    {
        return $this->repository->getList($user->getGuid());
    }

    /**
     * Delete OAuth access token
     * @param Access $tokenId
     * @return bool
     */
    public function delete(AccessTokenEntity $accessToken): bool
    {
        $accessTokens = $this->repository->getList($accessToken->getUserIdentifier());

        $accessTokens = array_filter($accessTokens, function (AccessTokenEntity $a) use ($accessToken) {
            return $a->getIdentifier() === $accessToken->getIdentifier();
        });

        if (!count($accessTokens)) {
            throw new UserErrorException('Invalid access token');
        }

        return $this->repository->revokeAccessToken($accessToken->getIdentifier());
    }
}
