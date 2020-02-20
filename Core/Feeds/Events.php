<?php
/**
 * Minds Feeds Events Listeners
 *
 * @author Mark / Ben
 */

namespace Minds\Core\Feeds;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;

class Events
{
    /** @var Dispatcher */
    protected $eventsDispatcher;

    /**
     * Events constructor.
     * @param Dispatcher $eventsDispatcher
     */
    public function __construct($eventsDispatcher = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
    }

    public function register()
    {
        // delete an activity
        $this->eventsDispatcher->register('activity:delete', 'all', function (Event $event) {
            $params = $event->getParameters();
            $activity = $params['activity'];
            $activity->delete();
        });
    }
}
