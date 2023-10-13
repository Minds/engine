<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Controllers;

use Minds\Core\Log\Logger;
use Minds\Core\Payments\Lago\Manager;
use Minds\Core\Payments\Lago\Types\Customer;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class CustomersController
{
    public function __construct(
        private readonly Manager $manager,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param Customer $customer
     * @return Customer
     */
    #[Mutation]
    public function createCustomer(
        Customer $customer
    ): Customer {
        $customer = $this->manager->createCustomer($customer);
        return $customer;
    }
}
