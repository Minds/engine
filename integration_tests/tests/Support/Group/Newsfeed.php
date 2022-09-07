<?php

declare(strict_types=1);

namespace Tests\Support\Group;

use Codeception\Event\TestEvent;
use Codeception\Platform\Group;

/**
 * Group class is Codeception Extension which is allowed to handle to all internal events.
 * This class itself can be used to listen events for test execution of one particular group.
 * It may be especially useful to create fixtures data, prepare server, etc.
 *
 * INSTALLATION:
 *
 * To use this group extension, include it to "extensions" option of global Codeception config.
 */

class Newsfeed extends Group
{
    public static $group = 'newsfeed';

    public function _before(TestEvent $e)
    {
    }

    public function _after(TestEvent $e)
    {
    }
}
