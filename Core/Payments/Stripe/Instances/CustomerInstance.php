<?php
namespace Minds\Core\Payments\Stripe\Instances;

use Minds\Common\StaticToInstance;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;

/**
 * @method AccountInstance create()
 * @method AccountInstance retrieve()
 */
class CustomerInstance extends StaticToInstance
{
    public function __construct(private ?StripeClient $stripeClient = null)
    {
        Di::_()->get('StripeSDK');
        $this->setClass(new \Stripe\Customer);
    }

    /**
     * Construct CustomerInstance for specific user (enabling test-mode key to be used).
     * @param User $user - user to construct for.
     * @return self
     */
    public function withUser(User $user): self
    {
        Di::_()->get('StripeSDK', [ 'user' => $user ]);
        $this->setClass(new \Stripe\Customer);
        return $this;
    }
}
