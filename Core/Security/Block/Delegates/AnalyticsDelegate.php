<?php
namespace Minds\Core\Security\Block\Delegates;

use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\Block\BlockEntry;

class AnalyticsDelegate
{
    public function __construct(
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Event $event = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->event ??= new Event();
    }

    /**
     * @param BlockEntry $blockEntry
     * @return void
     */
    public function onAdd(BlockEntry $blockEntry): void
    {
        $user = $this->entitiesBuilder->single($blockEntry->getActorGuid());

        $this->event->setType('action')
            ->setAction('block')
            ->setProduct('platform')
            ->setUserGuid($user->getGuid())
            ->setUserPhoneNumberHash($user->getPhoneNumberHash())
            ->setEntityGuid((string) $blockEntry->getSubjectGuid())
            ->setEntityType('user')
            ->push();
    }

    /**
     * @param BlockEntry $blockEntry
     * @return void
     */
    public function onDelete(BlockEntry $blockEntry): void
    {
        $user = $this->entitiesBuilder->single($blockEntry->getActorGuid());

        $this->event->setType('action')
            ->setAction('unblock')
            ->setProduct('platform')
            ->setUserGuid($user->getGuid())
            ->setUserPhoneNumberHash($user->getPhoneNumberHash())
            ->setEntityGuid((string) $blockEntry->getSubjectGuid())
            ->setEntityType('user')
            ->push();
    }
}
