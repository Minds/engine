<?php

namespace Spec\Minds\Core\OAuth\Managers;

use Minds\Core\OAuth\Entities\AccessTokenEntity;
use Minds\Core\OAuth\Entities\RefreshTokenEntity;
use Minds\Core\OAuth\Managers\AccessTokenManager;
use Minds\Core\OAuth\Repositories\AccessTokenRepository;
use Minds\Core\OAuth\Repositories\RefreshTokenRepository;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AccessTokenManagerSpec extends ObjectBehavior
{
    /** @var AccessTokenRepository */
    protected $repository;

    /** @var RefreshTokenRepository */
    private $refreshTokenRepository;

    public function let(AccessTokenRepository $repository, RefreshTokenRepository $refreshTokenRepository)
    {
        $this->beConstructedWith($repository, $refreshTokenRepository);
        $this->repository = $repository;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AccessTokenManager::class);
    }

    public function it_should_get_list_of_access_tokens()
    {
        $user = new User();
        $user->guid = '123';

        $accessToken = new AccessTokenEntity();
        $accessToken->setIdentifier('token-1');
        $accessToken->setUserIdentifier('123');

        $this->repository->getList('123')
            ->willReturn([
                $accessToken,
            ]);

        $this->getList($user);
    }

    public function it_should_delete_access_token()
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setIdentifier('token-1');
        $accessToken->setUserIdentifier('user-1');

        $this->repository->getList('user-1')
            ->willReturn([
                $accessToken,
            ]);

        $this->repository->revokeAccessToken('token-1')
                ->willReturn(true);

        $refreshToken = new RefreshTokenEntity();
        $refreshToken->setIdentifier('refresh-1');
        $this->refreshTokenRepository->getRefreshTokenFromAccessTokenId('token-1')
                ->willReturn($refreshToken);

        $this->refreshTokenRepository->revokeRefreshToken('refresh-1')
                ->shouldBeCalled();

        $this->delete($accessToken)->shouldBe(true);
    }

    public function it_should_delete_all_access_tokens()
    {
        $userId = 'user-1';

        $user = new User();
        $user->guid = $userId;

        $accessToken1 = new AccessTokenEntity();
        $accessToken1->setIdentifier('token-1');
        $accessToken1->setUserIdentifier($userId);

        $refreshToken1 = new RefreshTokenEntity();
        $refreshToken1->setIdentifier('refresh-1');

        $accessToken2 = new AccessTokenEntity();
        $accessToken2->setIdentifier('token-2');
        $accessToken2->setUserIdentifier($userId);

        $refreshToken2 = new RefreshTokenEntity();
        $refreshToken2->setIdentifier('refresh-2');

        $accessTokens = [$accessToken1, $accessToken2];

        $this->repository->getList($userId)
            ->willReturn(
                $accessTokens
            );

        // Confirm the access tokens get revoked

        $this->repository->revokeAccessToken('token-1')
            ->willReturn(true);

        $this->repository->revokeAccessToken('token-2')
            ->willReturn(true);


        // Confirm the related refresh tokens get revoked

        $this->refreshTokenRepository->getRefreshTokenFromAccessTokenId('token-1')
            ->willReturn($refreshToken1);

        $this->refreshTokenRepository->revokeRefreshToken('refresh-1')
                ->shouldBeCalled();

        $this->refreshTokenRepository->getRefreshTokenFromAccessTokenId('token-2')
            ->willReturn($refreshToken2);

        $this->refreshTokenRepository->revokeRefreshToken('refresh-2')
                ->shouldBeCalled();


        $this->deleteAll($user)->shouldBe(true);
    }
}
