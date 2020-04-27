<?php

namespace Minds\Core\Groups\Delegates;

use Minds\Core\Feeds\Activity\Actions\Delete;

/**
 * Class PropagateRejection
 *
 * Propagates activity deletion from the group feed after rejection.
 * @package Minds\Core\Groups\Delegates
 * @author Ben Hayward
 */
class PropagateRejectionDelegate
{
    /** @var Delete */
    protected $delete;

    public function __construct(
        Delete $delete = null
    ) {
        $this->delete = $delete ?? new Delete();
    }

    /**
     * Sends event to deletes an activity associated with a rejected post.
     *
     * @param string $guid - activity guid.
     */
    public function onReject($activity): void
    {
        $this->delete->setActivity($activity)
            ->delete();
    }
}
