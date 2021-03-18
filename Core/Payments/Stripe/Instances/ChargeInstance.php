<?php
namespace Minds\Core\Payments\Stripe\Instances;

use Minds\Common\StaticToInstance;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

/**
 * @method ChargeInstance retrieve()
 */
class ChargeInstance extends StaticToInstance
{
    public function __construct()
    {
        Di::_()->get('StripeSDK');
        $this->setClass(new \Stripe\Charge);
    }
}
