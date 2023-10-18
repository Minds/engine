<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\Lago\Services\CustomersService;
use Minds\Core\Payments\Lago\Types\Customer;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class CustomersController
{
    public function __construct(
        private readonly CustomersService $service,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param Customer $customer
     * @return Customer
     * @throws GuzzleException
     */
    #[Mutation]
    public function createCustomer(
        Customer $customer
    ): Customer {
        return $this->service->createCustomer($customer);
    }
}
