<?php

namespace Minds\Core\Payments\Stripe\Customers;

use Minds\Core\Config\Config;
use Minds\Core\Data\Cassandra\Thrift\Lookup;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;

class ManagerV2
{
    public function __construct(
        private ?Lookup $lookup = null,
        private ?StripeClient           $stripeClient = null,
        private ?Config            $config = null
    ) {
        $this->lookup = $lookup ?: Di::_()->get('Database\Cassandra\Lookup');
        $this->config ??= Di::_()->get('Config');
        $this->stripeClient ??= Di::_()->get(StripeClient::class);
    }

    /**
     * Return a Customer from user guid. Creates one if not found.
     * @param User $user
     * @return Customer
     * @throws ApiErrorException
     */
    public function getByUser(User $user): \Stripe\Customer
    {
        $customerId = $this->lookup->get("{$user->getGuid()}:payments")['customer_id'];

        if (!$customerId) {
            // TODO: add Minds user guid to stripe metadata
            $stripeCustomer = $this->stripeClient->customers->create([
                'email' => $user->getEmail(),
                'metadata' => [
                    'user_guid' => $user->getGuid(),
                ]
            ]);

            $this->lookup->set("{$user->getGuid()}:payments", [
                'customer_id' => (string) $stripeCustomer->id
            ]);

            return $stripeCustomer;
        }

        $stripeCustomer = $this->stripeClient->customers->retrieve($customerId);

        return $stripeCustomer;
    }
}
