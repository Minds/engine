<?php
/**
 * Maps internal events to EntitiesOps
 */

namespace Minds\Core\Entities\Ops;

use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;

class Events
{
    /** @var string */
    const EVENT_ID = 'entities-ops';

    public function __construct(
        private ?EventsDispatcher $eventsDispatcher = null,
        private ?EntitiesOpsTopic $entitiesOpsTopic = null
    ) {
        $this->eventsDispatcher ??= Di::_()->get('EventsDispatcher');
    }

    public function register()
    {
        if (!$this->entitiesOpsTopic) {
            $this->entitiesOpsTopic = new EntitiesOpsTopic();
        }

        /**
         * Creates an EntitiesOpsEvent from internal event call
         */
        $this->eventsDispatcher->register(self::EVENT_ID, 'all', function (Event $opts) {
            $event = new EntitiesOpsEvent();
            $event->setOp($opts->getNamespace())
                ->setEntityUrn($opts->getParameters()['entityUrn'])
                ->setTimestamp(time());

            $this->entitiesOpsTopic->send($event);
        });
    }
}
