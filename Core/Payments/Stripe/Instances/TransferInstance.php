<?php
namespace Minds\Core\Payments\Stripe\Instances;

use Minds\Common\StaticToInstance;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

/**
 * @method TransferInstance all()
 */
class TransferInstance extends StaticToInstance
{
    public function __construct(Config $config = null)
    {
        Di::_()->get('StripeSDK');
        $this->setClass(new \Stripe\Transfer);
    }
}
