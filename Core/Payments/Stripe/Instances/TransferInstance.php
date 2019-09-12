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
        $config = $config ?? Di::_()->get('Config');
        \Stripe\Stripe::setApiKey($config->get('payments')['stripe']['api_key']);
        $this->setClass(new \Stripe\Transfer);
    }
}
