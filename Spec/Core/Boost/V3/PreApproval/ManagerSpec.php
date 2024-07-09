<?php

namespace Spec\Minds\Core\Boost\V3\PreApproval;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Boost\V3\PreApproval\Manager as PreApprovalManager;
use Minds\Core\Boost\V3\Repository;
use Minds\Core\Config\Config;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $repository;
    private Collaborator $experimentsManager;
    private Collaborator $config;

    public function let(
        Repository $repository,
        ExperimentsManager $experimentsManager,
        Config $config
    ) {
        $this->repository = $repository;
        $this->experimentsManager = $experimentsManager;
        $this->config = $config;

        $this->beConstructedWith(
            $this->repository,
            $this->experimentsManager,
            $this->config
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(PreApprovalManager::class);
    }

    public function it_should_return_true_when_boost_should_be_preapproved(User $user): void
    {
        $userGuid = '123';
        $threshold = 3;

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->experimentsManager->isOn('front-5882-boost-preapprovals')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->config->get('boost')
            ->shouldBeCalled()
            ->willReturn(['pre_approval_threshold' => $threshold]);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->repository->getBoostStatusCounts(
            limit: Argument::any(),
            targetUserGuid: Argument::any(),
            statuses: Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn([
                BoostStatus::APPROVED => 1,
                BoostStatus::COMPLETED => 2,
            ]);
        
        $this->callOnWrappedObject('shouldPreApprove', [$user])
            ->shouldBe(true);
    }

    public function it_should_return_false_when_boost_should_not_be_approved_because_it_is_on_a_tenant_network(User $user): void
    {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(123);

        $this->experimentsManager->isOn('front-5882-boost-preapprovals')
            ->shouldNotBeCalled();
        
        $this->callOnWrappedObject('shouldPreApprove', [$user])
            ->shouldBe(false);
    }

    public function it_should_return_false_when_boost_should_NOT_be_preapproved_because_there_was_not_enough_boosts(User $user): void
    {
        $userGuid = '123';
        $threshold = 3;

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->experimentsManager->isOn('front-5882-boost-preapprovals')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->config->get('boost')
            ->shouldBeCalled()
            ->willReturn(['pre_approval_threshold' => $threshold]);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->repository->getBoostStatusCounts(
            limit: Argument::any(),
            targetUserGuid: Argument::any(),
            statuses: Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn([
                BoostStatus::APPROVED => 1,
                BoostStatus::COMPLETED => 1,
            ]);
        
        $this->callOnWrappedObject('shouldPreApprove', [$user])
            ->shouldBe(false);
    }

    public function it_should_return_false_when_boost_should_NOT_be_preapproved_because_there_was_reported_boosts(User $user): void
    {
        $userGuid = '123';
        $threshold = 3;


        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->experimentsManager->isOn('front-5882-boost-preapprovals')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->config->get('boost')
            ->shouldBeCalled()
            ->willReturn(['pre_approval_threshold' => $threshold]);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->repository->getBoostStatusCounts(
            limit: Argument::any(),
            targetUserGuid: Argument::any(),
            statuses: Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn([
                BoostStatus::REPORTED => 1,
                BoostStatus::COMPLETED => 2,
            ]);
        
        $this->callOnWrappedObject('shouldPreApprove', [$user])
            ->shouldBe(false);
    }

    public function it_should_return_false_when_boost_should_NOT_be_preapproved_because_there_was_rejected_boosts(User $user): void
    {
        $userGuid = '123';
        $threshold = 3;

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->experimentsManager->isOn('front-5882-boost-preapprovals')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->config->get('boost')
            ->shouldBeCalled()
            ->willReturn(['pre_approval_threshold' => $threshold]);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->repository->getBoostStatusCounts(
            limit: Argument::any(),
            targetUserGuid: Argument::any(),
            statuses: Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn([
                BoostStatus::REJECTED => 1,
                BoostStatus::COMPLETED => 2,
            ]);
        
        $this->callOnWrappedObject('shouldPreApprove', [$user])
            ->shouldBe(false);
    }

    public function it_should_return_false_when_boost_should_NOT_be_preapproved_because_experiment_is_off(User $user): void
    {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->experimentsManager->isOn('front-5882-boost-preapprovals')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->config->get('boost')
            ->shouldNotBeCalled();

        $this->callOnWrappedObject('shouldPreApprove', [$user])
            ->shouldBe(false);
    }
}
