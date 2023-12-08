<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Instances;

use Minds\Common\StaticToInstance;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use Stripe\Price;

class ProductPriceInstance extends StaticToInstance
{
    public function __construct(private ?StripeClient $stripeClient = null)
    {
        Di::_()->get('StripeSDK', ['api_version' => '2020-08-27']);
        $this->setClass(new Price());
    }

    public function withUser(User $user): self
    {
        Di::_()->get('StripeSDK', [ 'user' => $user, 'api_version' => '2020-08-27' ]);
        $this->setClass(new Price());
        return $this;
    }
}
