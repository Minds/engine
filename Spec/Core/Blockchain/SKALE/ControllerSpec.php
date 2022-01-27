<?php

namespace Spec\Minds\Core\Blockchain\SKALE;

use Minds\Core\Blockchain\SKALE\Controller;
use PhpSpec\ObjectBehavior;
use Minds\Core\Blockchain\SKALE\Manager;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    /** @var FeaturesManager */
    protected $featuresManager;

    public function let(
        Manager $manager,
        FeaturesManager $featuresManager,
    ) {
        $this->beConstructedWith($manager, $featuresManager);
        $this->manager = $manager;
        $this->featuresManager = $featuresManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_pass_valid_request_to_manager(
        ServerRequest $request,
        User $user,
    ) {
        $address = '0x123';
        $this->featuresManager->has('skale')
            ->shouldBeCalled()
            ->willReturn(true);

        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getParsedBody()
            ->shouldBeCalled()
            ->willReturn(['address' => $address]);

        $this->manager->requestFromFaucet($user, $address)
            ->shouldBeCalled()
            ->willReturn('0x321');

        $this->requestFromFaucet($request);
    }

    public function it_should_not_pass_request_if_skale_feature_not_enabled(
        ServerRequest $request,
    ) {
        $this->featuresManager->has('skale')
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->shouldThrow(ServerErrorException::class)
            ->during("requestFromFaucet", [$request]);
    }
}
