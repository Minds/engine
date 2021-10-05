<?php
namespace Minds\Core\Boost\Network\Delegates;

use Minds\Core\Boost\Network\Boost;

interface BoostDelegateInterface
{
    /**
     * Called when a boost is added
     * @param Boost $boost
     * @return void
     */
    public function onAdd(Boost $boost): void;
}
