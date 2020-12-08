<?php
/**
 * Minds Liquidity Positions Provider.
 */

namespace Minds\Core\Blockchain\LiquidityPositions;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Blockchain\LiquidityPositions\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Blockchain\LiquidityPositions\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);
    }
}
