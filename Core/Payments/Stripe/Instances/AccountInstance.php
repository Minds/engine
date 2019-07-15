<?php
namespace Minds\Core\Payments\Stripe\Instances;

use Minds\Common\StaticToInstance;

/**
 * @method AccountInstance create()
 * @method AccountInstance retrieve()
 */
class AccountInstance extends StaticToInstance
{

    public function __construct()
    {
        $this->setClass(new \Stripe\Account);
    }

}