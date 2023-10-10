<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago;

use Minds\Core\Payments\Lago\Clients\CustomersClient;

class Manager
{
    public function __construct(
        private readonly CustomersClient $customersClient,
    ) {
    }

    public function createCustomer(int $customerId): bool
    {
        return $this->customersClient->createCustomer($customerId);
    }
}
