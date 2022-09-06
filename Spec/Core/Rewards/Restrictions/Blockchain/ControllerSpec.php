<?php

namespace Spec\Minds\Core\Rewards\Restrictions\Blockchain;

use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;
use Minds\Core\Rewards\Restrictions\Blockchain\Manager;
use Minds\Core\Rewards\Restrictions\Blockchain\Controller;
use Minds\Core\Rewards\Restrictions\Blockchain\Exceptions\RestrictedException;
use Minds\Entities\User;
use Zend\Diactoros\Response\JsonResponse;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $restrictionsManager;

    public function let(Manager $restrictionsManager)
    {
        $this->restrictionsManager = $restrictionsManager;
        $this->beConstructedWith($restrictionsManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_check_if_an_address_is_NOT_restricted(ServerRequest $request, User $user)
    {
        $address = '0x00';

        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn(['address' => $address]);
        
        $this->restrictionsManager->gatekeeper($address, $user)
            ->shouldBeCalled();

        $response = $this->check($request);
        $response->shouldBeAnInstanceOf(JsonResponse::class);
        $response->getPayload()->shouldBe([
            'status' => 'success'
        ]);
    }

    public function it_should_check_if_an_address_is_restricted_and_propagate_exception_outward(ServerRequest $request, User $user)
    {
        $address = '0x00';

        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn(['address' => $address]);
        
        $this->restrictionsManager->gatekeeper($address, $user)
            ->willThrow(new RestrictedException());

        $this->shouldThrow(RestrictedException::class)
            ->during('check', [$request]);
    }
}
