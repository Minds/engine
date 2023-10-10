<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Clients;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;

class CustomersClient extends ApiClient
{
    public function __construct(
        HttpClient $httpClient
    ) {
        parent::__construct($httpClient);
    }

    /**
     * Creates a`new customer in Lago
     * @param int $customerId
     * @return bool
     * @throws GuzzleException
     */
    public function createCustomer(int $customerId): bool
    {
        $response = $this->httpClient->post(
            uri: '/api/v1/customers',
            options: [
                'json' => [
                    'customer' => [
                        'external_id' => $customerId,
                        'name' => 'Fausto Lago Test',
                        'billing_configuration' => [
                            'payment_provider' => 'stripe',
                            'provider_payment_methods' => ['card'],
                            'sync_with_provider' => true
                        ]
                    ]
                ]
            ]
        );

        var_export($response->getBody()->getContents(), true);

        return true;
    }
}
