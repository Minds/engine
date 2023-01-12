<?php

declare(strict_types=1);

namespace Spec\Minds\Core\Boost\V3\Delegates;

use Minds\Core\Boost\V3\Delegates\ActionEventDelegate;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ActionEventDelegateSpec extends ObjectBehavior
{
    /** @var ActionEventsTopic */
    private $actionEventsTopic;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var ActiveSession */
    private $activeSession;

    public function let(
        ActionEventsTopic $actionEventsTopic,
        EntitiesBuilder $entitiesBuilder,
        ActiveSession $activeSession
    ) {
        $this->actionEventsTopic = $actionEventsTopic;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->activeSession = $activeSession;

        $this->beConstructedWith(
            $actionEventsTopic,
            $entitiesBuilder,
            $activeSession
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(ActionEventDelegate::class);
    }

    public function it_does_dispatch_action_event_on_approve(
        Boost $boost,
        User $sender
    ): void {
        // This path is forced by the CLI and not the user that will
        // be retrieved for all action events.
        $this->entitiesBuilder->single('100000000000000519')
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->actionEventsTopic->send(Argument::that(function ($arg) use ($boost, $sender) {
            return $arg->getEntity() === $boost->getWrappedObject() &&
                $arg->getUser() === $sender->getWrappedObject() &&
                $arg->getAction() === 'boost_accepted';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onApprove($boost);
    }

    public function it_does_dispatch_action_event_on_reject(
        Boost $boost,
        User $sender
    ): void {
        $rejectionReason = 22;
        
        // This path is forced by the CLI and not the user that will
        // be retrieved for all action events.
        $this->entitiesBuilder->single('100000000000000519')
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->actionEventsTopic->send(Argument::that(function ($arg) use ($boost, $sender, $rejectionReason) {
            return $arg->getEntity() === $boost->getWrappedObject() &&
                $arg->getUser() === $sender->getWrappedObject() &&
                $arg->getAction() === 'boost_rejected' &&
                $arg->getActionData() === [
                    'boost_reject_reason' => $rejectionReason
                ];
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onReject($boost, $rejectionReason);
    }

    public function it_does_dispatch_action_event_on_complete(
        Boost $boost,
        User $sender
    ): void {
        $this->entitiesBuilder->single('100000000000000519')
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->actionEventsTopic->send(Argument::that(function ($arg) use ($boost, $sender) {
            return $arg->getEntity() === $boost->getWrappedObject() &&
                $arg->getUser() === $sender->getWrappedObject() &&
                $arg->getAction() === 'boost_completed';
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onComplete($boost);
    }
}
