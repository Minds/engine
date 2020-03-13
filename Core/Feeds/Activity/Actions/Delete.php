<?php
/**
 * Minds Entities Delete action
 *
 * @author Mark / Ben
 */
namespace Minds\Core\Feeds\Activity\Actions;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;

class Delete
{
    /** @var Dispatcher */
    protected $eventsDispatcher;

    /** @var mixed */
    protected $activity;

    /**
     * Save constructor.
     * @param null $eventsDispatcher
     */
    public function __construct(
        $eventsDispatcher = null
    ) {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
    }

    /**
     * Sets the activity
     * @param mixed $activity
     * @return Save
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;
        return $this;
    }

    /**
     * Delete the activity
     * @param mixed ...$args
     * @return bool
     * @throws \Minds\Exceptions\StopEventException
     */
    public function delete(...$args)
    {
        if (!$this->activity) {
            return false;
        }

        return $this->eventsDispatcher->trigger('activity:delete', 'all', [
            'activity' => $this->activity,
        ], false);
    }
}
