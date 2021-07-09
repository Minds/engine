<?php
/**
 * Blockchain module.
 *
 * !! This is only used for submodule purposes at the moment !!
 */

namespace Minds\Core\Blockchain;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /** @var array */
    public $submodules = [
        LiquidityPositions\Module::class,
        Wallets\OnChain\UniqueOnChain\Module::class,
        TokenPrices\Module::class,
        Metrics\Module::class,
    ];

    /**
     * OnInit.
     */
    public function onInit()
    {
        // TODO: currently we are only using this to make new submodules easier to manager
    }
}
