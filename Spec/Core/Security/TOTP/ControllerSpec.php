<?php

namespace Spec\Minds\Core\Security\TOTP;

use Minds\Common\Repository\Response;
use Minds\Core\Security\TOTP\Controller;
use Minds\Core\Security\TOTP\Manager;
use Minds\Core\Security\TOTP\TOTPSecret;
use Minds\Core\Security\TOTP\TOTPSecretQueryOpts;
use Minds\Core\Security\TwoFactor;
use Exception;
use Minds\Exceptions\UserErrorException;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    /** @var TwoFactor */
    protected $twoFactor;

    public function let(Manager $manager, TwoFactor $twoFactor)
    {
        $this->beConstructedWith($manager, $twoFactor);
        $this->manager = $manager;
        $this->twoFactor = $twoFactor;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_create_new_secret_if_not_registered(
        ServerRequest $request
    ) {
        $user = new User();
        $user->guid = '123';

        $request->getAttribute('_user')
            ->willReturn($user);

        $opts = new TOTPSecretQueryOpts();
        $opts->setUserGuid($user->getGuid);

        $this->manager->isRegistered($user)
            ->willReturn(false);

        $this->twoFactor->createSecret()
            ->willReturn('abcdefgh01234567');

        $response = $this->createNewSecret($request);
        $json = $response->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success',
            'secret' => 'abcdefgh01234567'
        ]));
    }
}
