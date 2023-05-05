<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Feeds\Activity\Delegates;

use Minds\Core\Feeds\Activity\Delegates\TimeCreatedDelegate;
use Minds\Core\Feeds\Scheduled\EntityTimeCreated;
use Minds\Entities\Activity;
use Minds\Exceptions\AlreadyPublishedException;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class TimeCreatedDelegateSpec extends ObjectBehavior
{
    protected Collaborator $entityTimeCreated;

    public function let(
        EntityTimeCreated $entityTimeCreated
    ) {
        $this->entityTimeCreated = $entityTimeCreated;
        $this->beConstructedWith($entityTimeCreated);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(TimeCreatedDelegate::class);
    }
    
    public function it_should_validate_on_update(Activity $entity): void
    {
        $timeCreated = time();
        $timeSent = time() + 1;

        $this->entityTimeCreated->validate(
            entity: $entity,
            time_created: $timeCreated,
            time_sent: $timeSent,
            action: EntityTimeCreated::UPDATE_ACTION
        )->shouldBeCalled();

        $this->onUpdate($entity, $timeCreated, $timeSent)->shouldBe(true);
    }

    public function it_should_validate_on_update_and_silently_fail_if_already_published(Activity $entity): void
    {
        $timeCreated = time();
        $timeSent = time() + 1;

        $this->entityTimeCreated->validate(
            entity: $entity,
            time_created: $timeCreated,
            time_sent: $timeSent,
            action: EntityTimeCreated::UPDATE_ACTION
        )
            ->shouldBeCalled()
            ->willThrow(new AlreadyPublishedException());

        $this->onUpdate($entity, $timeCreated, $timeSent)->shouldBe(true);
    }

    public function it_should_validate_on_update_and_throw_unhandled_exceptions_outward(Activity $entity): void
    {
        $timeCreated = time();
        $timeSent = time() + 1;

        $this->entityTimeCreated->validate(
            entity: $entity,
            time_created: $timeCreated,
            time_sent: $timeSent,
            action: EntityTimeCreated::UPDATE_ACTION
        )
            ->shouldBeCalled()
            ->willThrow(new ServerErrorException());

        $this->shouldThrow(new ServerErrorException())->during('onUpdate', [$entity, $timeCreated, $timeSent]);
    }
}
