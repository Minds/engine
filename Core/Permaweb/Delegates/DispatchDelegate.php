<?php
/**
 * DispatchDelegate -
 *
 * Dispatches request to permaweb server to save to the arweave network.
 * @author Ben Hayward
 */
namespace Minds\Core\Permaweb\Delegates;

use Minds\Core\Di\Di;

class DispatchDelegate extends AbstractPermawebDelegate
{
    public $manager;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?: Di::_()->get('Permaweb\Manager');
    }

    /**
     * Dispatch save call.
     */
    public function dispatch(): void
    {
        $this->manager->save(
            parent::assembleOpts()
        );
    }
}
