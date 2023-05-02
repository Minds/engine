<?php
declare(strict_types=1);
/**
 * Referrals module
 */

namespace Minds\Core\Referrals;

use Minds\Interfaces\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * OnInit.
     */
    public function onInit(): void
    {
        $provider = new Provider();
        $provider->register();
        (new Routes())->register();
    }
}
