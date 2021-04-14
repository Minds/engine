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
}
