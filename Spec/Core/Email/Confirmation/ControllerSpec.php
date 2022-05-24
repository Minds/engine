<?php

namespace Spec\Minds\Core\Email\Confirmation;

use PhpSpec\ObjectBehavior;
use Minds\Core\Email\Confirmation\Controller;
use Minds\Core\Security\TwoFactor\Manager;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Entities\User;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    public function let(Manager $manager)
    {
        $this->beConstructedWith($manager);
        $this->manager = $manager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_is_should_call_gatekeeper_to_confirm_email_and_return_success_if_passed(
        ServerRequest $request,
        User $user
    ) {
        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $this->manager->gatekeeper($user, Argument::any())
            ->shouldBeCalled();

        $jsonResponse = $this->confirmEmail($request);

        $json = $jsonResponse->getBody()->getContents();

        $json->shouldBe(json_encode([
            'status' => 'success'
        ]));
    }

    public function it_is_should_call_gatekeeper_to_confirm_email_and_NOT_return_success_if_error_thrown(
        ServerRequest $request,
        User $user
    ) {
        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $this->manager->gatekeeper($user, Argument::any())
            ->shouldBeCalled()
            ->willThrow(new TwoFactorRequiredException());

        $this->shouldThrow(TwoFactorRequiredException::class)
            ->during('confirmEmail', [$request]);
    }
}
