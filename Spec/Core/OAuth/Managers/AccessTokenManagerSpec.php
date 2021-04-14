<?php

namespace Spec\Minds\Core\OAuth\Managers;

use Minds\Core\OAuth\Entities\AccessTokenEntity;
use Minds\Core\OAuth\Managers\AccessTokenManager;
use Minds\Core\OAuth\Repositories\AccessTokenRepository;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AccessTokenManagerSpec extends ObjectBehavior
{
    /** @var AccessTokenRepository */
    protected $repository;

    public function let(AccessTokenRepository $repository)
    {
        $this->beConstructedWith($repository);
        $this->repository = $repository;
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

        $this->delete($accessToken)->shouldBe(true);
    }
}
