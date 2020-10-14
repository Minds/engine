<?php

namespace Spec\Minds\Core\Onboarding;

use Minds\Core\Onboarding\Controller;
use Minds\Core\Onboarding\Manager;
use Minds\Core\Onboarding\OnboardingGroups;
use Minds\Entities\User;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

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

    public function it_should_return_initial_onboarding_progress(ServerRequest $request, OnboardingGroups\InitialOnboardingGroup $initialOnboardingGroup)
    {
        $user = new User();

        $request->getAttribute('_user')
            ->willReturn($user);

        $this->manager->setUser($user)
            ->willReturn($this->manager);

        $this->manager->getOnboardingGroup()
            ->willReturn($initialOnboardingGroup);

        $initialOnboardingGroup->export()
            ->willReturn([
                'is_completed' => false,
                'steps' => [],
            ]);
        //
            
        $response = $this->getProgress($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe(json_encode([
            'status' => 'success',
            'is_completed' => false,
            'steps' => [],
        ]));
    }

    public function it_should_return_ongoing_onboarding_progress(ServerRequest $request, OnboardingGroups\OngoingOnboardingGroup $ongoingOnboardingGroup)
    {
        $user = new User();

        $request->getAttribute('_user')
            ->willReturn($user);

        $this->manager->setUser($user)
            ->willReturn($this->manager);

        $this->manager->getOnboardingGroup()
            ->willReturn($ongoingOnboardingGroup);

        $ongoingOnboardingGroup->export()
            ->willReturn([
                'is_completed' => false,
                'steps' => [],
            ]);
        //
            
        $response = $this->getProgress($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe(json_encode([
            'status' => 'success',
            'is_completed' => false,
            'steps' => [],
        ]));
    }

    public function it_should_set_onboarding_seen_ts(ServerRequest $request)
    {
        $user = new User();

        $request->getAttribute('_user')
            ->willReturn($user);

        $this->manager->setUser($user)
            ->willReturn($this->manager);

        $this->manager->setOnboardingShown(Argument::any())
            ->willReturn(true);
        
        //

        $response = $this->setSeen($request);
        $json = $response->getBody()->getContents();
        $json->shouldBe(json_encode([
            'status' => 'success',
        ]));
    }
}
