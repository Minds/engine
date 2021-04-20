<?php
/**
 * Minds Access Token Manager
 */

namespace Minds\Core\OAuth\Managers;

use Minds\Core;
use Minds\Core\OAuth\Entities\AccessTokenEntity;
use Minds\Core\OAuth\Repositories\AccessTokenRepository;
use Minds\Core\OAuth\Repositories\RefreshTokenRepository;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

class AccessTokenManager
{
    /** @var AccessTokenRepository */
    private $repository;

    /** @var RefreshTokenRepository */
    private $refreshTokenRepository;

    public function __construct(
        $repository = null,
        $refreshTokenRepository = null
    ) {
        $this->repository = $repository ?? new AccessTokenRepository;
        $this->refreshTokenRepository = $refreshTokenRepository ?? new RefreshTokenRepository();
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
     * @param AccessTokenEntity $accessToken
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

        // Fetch the associated refresh token, and revoke that too
        $refreshToken = $this->refreshTokenRepository->getRefreshTokenFromAccessTokenId($accessToken->getIdentifier());
        if ($refreshToken) {
            $this->refreshTokenRepository->revokeRefreshToken($refreshToken->getIdentifier());
        }

        return $this->repository->revokeAccessToken($accessToken->getIdentifier());
    }
}
