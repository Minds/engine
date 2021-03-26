<?php

namespace Spec\Minds\Core\Security\TOTP;

use Minds\Core\Security\TOTP\Manager;
use Minds\Core\Security\TOTP\Repository;
use Minds\Core\Security\TOTP\TOTPSecret;
use Minds\Core\Security\TOTP\TOTPSecretQueryOpts;
use Minds\Core\Security\TOTP\Delegates\EmailDelegate;
use Minds\Core\Security\Password;
use Minds\Common\Repository\Response;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Exception;
use Minds\Core\EntitiesBuilder;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var Password */
    protected $password;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Delegates\EmailDelegate */
    private $emailDelegate;

    public function let(Repository $repository, Password $password, EntitiesBuilder $entitiesBuilder, EmailDelegate $emailDelegate)
    {
        $this->beConstructedWith($repository, $password, $entitiesBuilder, $emailDelegate);

        $this->repository = $repository;
        $this->password = $password;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->emailDelegate = $emailDelegate;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_secret()
    {
        $opts = new TOTPSecretQueryOpts();
        $opts->setUserGuid('123')
            ->setDeviceId('app');

        $totpSecret = new TOTPSecret();
        $totpSecret->setUserGuid($opts->getUserGuid())
            ->setDeviceId($opts->getDeviceId())
            ->setSecret('abcdefgh01234567');

        $this->repository->get($opts)
            ->shouldBeCalled()
            ->willReturn($totpSecret);

        $this->get($opts)
            ->shouldReturn($totpSecret);
    }

    public function it_should_add_secret()
    {
        $totpSecret = new TOTPSecret();
        $totpSecret->setUserGuid('123')
            ->setDeviceId('app')
            ->setSecret('abcdefgh01234567')
            ->setRecoveryHash('wx.yz');

        $this->repository->add($totpSecret)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($totpSecret)
            ->shouldBe(true);
    }

    public function it_should_remove_secret()
    {
        $opts = new TOTPSecretQueryOpts();
        $opts->setUserGuid('123')
            ->setDeviceId('app');

        $this->repository->delete($opts)
            ->willReturn(true);

        $this->delete($opts)
            ->shouldBe(true);
    }

    public function it_should_reset_totp_device_with_code()
    {
        $userGuid = '123';
        $user = new User();
        $user->guid = $userGuid;

        $this->entitiesBuilder->getByUserByIndex('myusername')
            ->willReturn($user);

        $this->password->check($user, 'testpassword')
            ->willReturn(true);

        $opts = new TOTPSecretQueryOpts();
        $opts->setUserGuid($userGuid);

        $totpSecret = new TOTPSecret();
        $totpSecret->setUserGuid('123')
            ->setDeviceId('app')
            ->setSecret('abcdefgh01234567')
            ->setRecoveryHash('$2y$10$oGCiGfwd4VHmpDkiiGdBje8b0.tB6W9Cz/QCCQv1Xjp4XEtsko8um');

        $this->repository->get($opts)
            ->willReturn($totpSecret);

        $this->repository->delete($opts)
            ->willReturn(true);

        $this->emailDelegate->onRecover($user)
            ->shouldBeCalled();

        $this->recover('myusername', 'testpassword', '67899876')
            ->shouldBe(true);
    }
}
