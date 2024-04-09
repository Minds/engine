<?php

declare(strict_types=1);

namespace Spec\Minds\Core\Boost\V3\Delegates;

use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Boost\V3\Delegates\ActionEventDelegate;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Enums\BoostTargetSuitability;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ActionEventDelegateSpec extends ObjectBehavior
{
    /** @var ActionEventsTopic */
    private $actionEventsTopic;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var ActiveSession */
    private $activeSession;

    private Collaborator $postHogServiceMock;

    public function let(
        ActionEventsTopic $actionEventsTopic,
        EntitiesBuilder $entitiesBuilder,
        ActiveSession $activeSession,
        PostHogService $postHogServiceMock,
    ) {
        $this->actionEventsTopic = $actionEventsTopic;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->activeSession = $activeSession;
        $this->postHogServiceMock = $postHogServiceMock;

        $this->beConstructedWith(
            $actionEventsTopic,
            $entitiesBuilder,
            $activeSession,
            $postHogServiceMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(ActionEventDelegate::class);
    }

    public function it_does_dispatch_action_event_on_approve(
        User $sender
    ): void {
        $boost = new Boost('123', BoostTargetLocation::NEWSFEED, BoostTargetSuitability::SAFE, BoostPaymentMethod::CASH, 1, 1, 1);
        $boost->setGuid('456');
        $boost->setOwnerGuid('789');

        // This path is forced by the CLI and not the user that will
        // be retrieved for all action events.
        $this->entitiesBuilder->single('100000000000000519')
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->actionEventsTopic->send(Argument::that(function ($arg) use ($boost, $sender) {
            return $arg->getEntity() === $boost &&
                $arg->getUser() === $sender->getWrappedObject() &&
                $arg->getAction() === 'boost_accepted';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->postHogServiceMock->capture('boost_accepted', $sender, Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onApprove($boost);
    }

    public function it_does_dispatch_action_event_on_reject(
        User $sender
    ): void {
        $boost = new Boost('123', BoostTargetLocation::NEWSFEED, BoostTargetSuitability::SAFE, BoostPaymentMethod::CASH, 1, 1, 1);
        $boost->setGuid('456');
        $boost->setOwnerGuid('789');

        $rejectionReason = 22;
        
        // This path is forced by the CLI and not the user that will
        // be retrieved for all action events.
        $this->entitiesBuilder->single('100000000000000519')
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->actionEventsTopic->send(Argument::that(function ($arg) use ($boost, $sender, $rejectionReason) {
            return $arg->getEntity() === $boost &&
                $arg->getUser() === $sender->getWrappedObject() &&
                $arg->getAction() === 'boost_rejected' &&
                $arg->getActionData() === [
                    'boost_reject_reason' => $rejectionReason
                ];
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->postHogServiceMock->capture('boost_rejected', $sender, Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onReject($boost, $rejectionReason);
    }

    public function it_does_dispatch_action_event_on_complete(
        User $sender
    ): void {
        $boost = new Boost('123', BoostTargetLocation::NEWSFEED, BoostTargetSuitability::SAFE, BoostPaymentMethod::CASH, 1, 1, 1);
        $boost->setGuid('456');
        $boost->setOwnerGuid('789');

        $this->entitiesBuilder->single('100000000000000519')
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->actionEventsTopic->send(Argument::that(function ($arg) use ($boost, $sender) {
            return $arg->getEntity() === $boost &&
                $arg->getUser() === $sender->getWrappedObject() &&
                $arg->getAction() === 'boost_completed';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->postHogServiceMock->capture('boost_completed', $sender, Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onComplete($boost);
    }
}
