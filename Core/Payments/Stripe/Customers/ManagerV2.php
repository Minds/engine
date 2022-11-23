<?php

namespace Minds\Core\Payments\Stripe\Customers;

use Minds\Core\Config\Config;
use Minds\Core\Data\Cassandra\Thrift\Lookup;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;

class ManagerV2
{
    public function __construct(
        private ?Lookup $lookup = null,
        private ?StripeClient           $stripeClient = null,
        private ?Config            $config = null
    ) {
        $this->lookup = $lookup ?: Di::_()->get('Database\Cassandra\Lookup');
        $this->config ??= Di::_()->get('Config');
        $this->stripeClient ??= new StripeClient();
    }

    /**
     * Return a Customer from user guid. Creates one if not found.
     * @param string $userGuid
     * @return Customer
     */
    public function getByUser(User $user): \Stripe\Customer
    {
        $customerId = $this->lookup->get("{$user->getGuid()}:payments")['customer_id'];

        if (!$customerId) {
            $stripeCustomer = $this->stripeClient->customers->create([]);

            $this->lookup->set("{$user->getGuid()}:payments", [
                'customer_id' => (string) $stripeCustomer->id
            ]);

            return $stripeCustomer;
        }

        $stripeCustomer = $this->stripeClient->customers->retrieve($customerId);

        return $stripeCustomer;
    }
}
