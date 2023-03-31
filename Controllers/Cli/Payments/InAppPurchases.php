<?php

namespace Minds\Controllers\Cli\Payments;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Google\GoogleInAppPurchasesPubSub;
use Minds\Core\Payments\Subscriptions\Manager;
use Minds\Core\Payments\Subscriptions\Queue;
use Minds\Core\Security\ACL;
use Minds\Helpers\Cql;
use Minds\Interfaces;
use Minds\Core\Util\BigNumber;

class InAppPurchases extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        $this->out('Syntax usage: payments inapppurchases [run]');
    }

    public function exec()
    {
        $this->help();
    }

    /**
     * This function will process in app purchase receipt from pub sub
     * Mainly used for renewal processing
     */
    public function googlePubSub()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        $pubSub = Di::_()->get(GoogleInAppPurchasesPubSub::class);
        $pubSub->receivePubSubMessages();
    }
}
