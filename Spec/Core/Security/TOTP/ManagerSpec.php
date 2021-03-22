<?php

namespace Spec\Minds\Core\Security\TOTP;

use Minds\Core\Security\TOTP\Manager;
use Minds\Core\Security\TOTP\Repository;
use Minds\Core\Security\TOTP\TOTPSecret;
use Minds\Core\Security\TOTP\TOTPSecretQueryOpts;
use Minds\Common\Repository\Response;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Exception;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    public function let(Repository $repository)
    {
        $this->beConstructedWith($repository);
        $this->repository = $repository;
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
            ->setSecret('abcdefgh01234567');

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
}
