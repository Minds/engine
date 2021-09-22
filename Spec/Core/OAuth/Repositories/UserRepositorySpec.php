<?php

namespace Spec\Minds\Core\OAuth\Repositories;

use Minds\Core\OAuth\Repositories\UserRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Entities\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Minds\Core\Security\TwoFactor;

class UserRepositorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(UserRepository::class);
    }

    public function it_should_return_a_user_with_credentials(
        ClientEntityInterface $clientEntity
    ) {
        $this->mockUser = new User;
        $this->mockUser->guid = 123;
        $this->mockUser->password = password_hash('testpassword', PASSWORD_BCRYPT);

        $_SERVER['HTTP_APP_VERSION'] = '4.10.0'; // temp requirement until build is widely available

        $userEntity = $this->getUserEntityByUserCredentials(
            'spec-user-test',
            'testpassword',
            'password',
            $clientEntity
        );

        $userEntity->getIdentifier()
            ->shouldReturn('123');
    }

    public function it_should_not_return_a_user_with_bad_credentials(
        ClientEntityInterface $clientEntity
    ) {
        $this->mockUser = new User;
        $this->mockUser->guid = 123;
        $this->mockUser->password = password_hash('testpassword', PASSWORD_BCRYPT);

        $userEntity = $this->getUserEntityByUserCredentials(
            'spec-user-test',
            'wrongtestpassword',
            'password',
            $clientEntity
        );

        $userEntity->shouldReturn(false);
    }

    public function it_should_return_a_user_with_credentials_and_2fa(
        ClientEntityInterface $clientEntity,
        TwoFactor\Manager $twoFactorManager
    ) {
        $this->beConstructedWith(null, null, $twoFactorManager);

        $this->mockUser = new User;
        $this->mockUser->guid = 123;
        $this->mockUser->password = password_hash('testpassword', PASSWORD_BCRYPT);

        $_SERVER['HTTP_APP_VERSION'] = '4.10.0'; // temp requirement until build is widely available

        $twoFactorManager->gatekeeper($this->mockUser, Argument::any(), enableEmail: false)
            ->shouldBeCalled();

        $userEntity = $this->getUserEntityByUserCredentials(
            'spec-user-test',
            'testpassword',
            'password',
            $clientEntity
        );

        $userEntity->getIdentifier()
            ->shouldReturn('123');
    }
}
