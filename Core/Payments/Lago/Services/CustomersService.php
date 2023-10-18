<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Services;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\Lago\Clients\CustomersClient;
use Minds\Core\Payments\Lago\Types\Customer;

class CustomersService
{
    public function __construct(
        private readonly CustomersClient $customersClient,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param Customer $customer
     * @return Customer
     * @throws GuzzleException
     */
    public function createCustomer(Customer $customer): Customer
    {
        return $this->customersClient->createCustomer($customer);
    }
}
