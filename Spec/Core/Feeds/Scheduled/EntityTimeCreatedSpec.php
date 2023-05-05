<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Feeds\Scheduled;

use Minds\Core\Feeds\Scheduled\EntityTimeCreated;
use Minds\Entities\Activity;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;

class EntityTimeCreatedSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(EntityTimeCreated::class);
    }

    public function it_should_validate_a_valid_scheduled_post_on_create(
        Activity $entity
    ): void {
        $timeCreated = strtotime('+2 Months');
        $timeSent = time();

        $entity->setTimeCreated($timeCreated)
            ->shouldBeCalled();

        $entity->setTimeSent($timeSent)
            ->shouldBeCalled();

        $this->validate($entity, $timeCreated, $timeSent, $this->CREATE_ACTION);
    }

    public function it_should_validate_a_valid_scheduled_post_on_update(
        Activity $entity
    ): void {
        $timeCreated = strtotime('+2 Months');
        $timeSent = time();

        $entity->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(strtotime('+1 Month'));

        $entity->setTimeCreated($timeCreated)
            ->shouldBeCalled();

        $entity->setTimeSent($timeSent)
            ->shouldBeCalled();

        $this->validate($entity, $timeCreated, $timeSent, $this->UPDATE_ACTION);
    }

    public function it_should_set_time_created_to_time_sent_if_short_difference(
        Activity $entity
    ): void {
        $timeCreated = strtotime('+1 minute');
        $timeSent = time();

        $entity->setTimeCreated($timeSent)
            ->shouldBeCalled();

        $entity->setTimeSent($timeSent)
            ->shouldBeCalled();

        $this->validate($entity, $timeCreated, $timeSent, $this->CREATE_ACTION);
    }

    public function it_should_throw_exception_if_too_far_in_future_for_create_action(
        Activity $entity
    ): void {
        $timeCreated = strtotime('+4 Months');
        $timeSent = time();

        $this->shouldThrow(new UserErrorException(
            'Time is too far in the future',
            400
        ))->during('validate', [$entity, $timeCreated, $timeSent, $this->CREATE_ACTION]);
    }

    public function it_should_throw_exception_if_too_far_in_future_for_update_action(
        Activity $entity
    ): void {
        $timeCreated = strtotime('+4 Months');
        $timeSent = time();

        $this->shouldThrow(new UserErrorException(
            'Time is too far in the future',
            400
        ))->during('validate', [$entity, $timeCreated, $timeSent, $this->UPDATE_ACTION]);
    }
}
