<?php

namespace Spec\Minds\Core\Notifications\Delegates;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Events\Dispatcher;
use Minds\Entities\User;
use Minds\Core\Notifications\Notification as NotificationEntity;
use Minds\Entities\Entity;

class EventsDelegateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Notifications\Delegates\EventsDelegate');
    }

    public function it_should_return_an_array_of_notifications_when_handling_event(
        User $from_user,
        User $to_user_a,
        User $to_user_b,
        Entity $entity
    ) {
        $this::registerEvents();

        expect(Dispatcher::trigger('notification', 'mock', [
            'to' => [ $to_user_a->guid, $to_user_b->guid ],
            'from' => $from_user,
            'entity' => $entity->getWrappedObject(),
            'notification_view' => 'mock_test',
            'description' => 'I am a mock',
            'params' => [ 'message' => 'I am foobar' ],
            'dry' => true
        ]))->shouldReturnArrayOfNotifications();
    }

    public function getMatchers(): array
    {
        return [
            'returnArrayOfNotifications' => function ($array) {
                if (!is_array($array)) {
                    return false;
                }

                foreach ($array as $item) {
                    if (!($item instanceof NotificationEntity)) {
                        return false;
                    }
                }

                return true;
            }
        ];
    }
}
