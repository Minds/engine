<?php

/**
 * Minds Feeds Events Listeners
 *
 * @author Mark / Ben
 */

namespace Minds\Core\Feeds;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Core\Security\Block;
use Minds\Core\Data\cache\PsrWrapper;

class Events
{
    /** @var Dispatcher */
    protected $eventsDispatcher;

    /** @var Block\Manager */
    protected $blockManager;

    /**
     * Events constructor.
     * @param Dispatcher $eventsDispatcher
     * @param Block\Manager $blockManager
     */
    public function __construct($eventsDispatcher = null, $blockManager = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->blockManager = $blockManager ?? Di::_()->get('Security\Block\Manager');
    }

    public function register()
    {
        // delete an activity
        // This needs to be refactored to use new 'entity:delete'
        $this->eventsDispatcher->register('activity:delete', 'all', function (Event $event) {
            $params = $event->getParameters();
            $activity = $params['activity'];
            $activity->delete();
        });

        $this->eventsDispatcher->register('entity:delete', 'activity', function (Event $event) {
            $params = $event->getParameters();
            $activity = $params['entity'];
            $event->setResponse($activity->delete());
        });

        /**
         * Prevent seeing reminds of blocked channels
         * Returning true means post will not pass ACL
         */
        $this->eventsDispatcher->register('acl:read:blacklist', 'activity', function ($event) {
            $params = $event->getParameters();
            $activity = $params['entity'];
            $user = $params['user'];

            if ($activity->remind_object) {
                $remindObj = $activity->remind_object;
                $blockEntry = (new Block\BlockEntry())
                    ->setActor($user)
                    ->setSubjectGuid($remindObj['owner_guid']);
                $event->setResponse($this->blockManager->hasBlocked($blockEntry));
            }
        });

        /**
         * Add remind and quote counts to entities
         * NOTE: Remind not moved over yet, lets see how quote counts scale
         */
        $this->eventsDispatcher->register('export:extender', 'activity', function ($event) {
            $params = $event->getParameters();
            $activity = $params['entity'];
            $export = $event->response() ?: [];

            $cacheKey = 'interactions:count:' . $activity->getGuid();

            /** @var PsrWrapper */
            $psrCache = Di::_()->get('Cache\PsrWrapper');

            if ($quoteCount = $psrCache->get($cacheKey)) {
                $export['quotes'] = $quoteCount;
            } else {
                /** @var Elastic\Manager */
                $feedsManager = Di::_()->get('Feeds\Elastic\Manager');

                $export['quotes'] = $feedsManager->getCount([
                    'algorithm' => 'latest',
                    'type' => 'activity',
                    'period' => 'all',
                    'quote_guid' => $activity->getGuid(),
                ]);

                $psrCache->set($cacheKey, $export['quotes'], 900); // Cache for 15 minutes
            }

            $event->setResponse($export);
        });
    }
}
