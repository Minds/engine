<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\Clicks\Delegates;

use Minds\Core\Analytics\Metrics\Event;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;

/**
 * Responsible for dispatching click actions to PostHog via Events.
 */
class PostHogDelegate
{
    public function __construct(
        private ?Event $event = null
    ) {
        $this->event ??= new Event();
    }

    /**
     * Called when a click is to be recorded.
     * @param EntityInterface $entity - entity that has been clicked.
     * @param User $user - user who clicked.
     * @param array $clientMeta - associated client meta.
     * @return void
     */
    public function onClick(
        EntityInterface $entity,
        array $clientMeta,
        User $user
    ): void {
        $this->event->setUser($user)
            ->setType('action')
            ->setAction('click')
            ->setEntityGuid($entity->getGuid())
            ->setClientMeta($clientMeta)
            ->push(shouldIndex: false);
    }
}
