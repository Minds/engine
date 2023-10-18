<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Clients;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Payments\Lago\Enums\PaymentProviderEnum;
use Minds\Core\Payments\Lago\Types\BillingConfiguration;
use Minds\Core\Payments\Lago\Types\Customer;

class CustomersClient extends ApiClient
{
    public function __construct(
        HttpClient $httpClient
    ) {
        parent::__construct($httpClient);
    }

    /**
     * Creates a`new customer in Lago
     * @param Customer $customer
     * @return Customer The created customer from the response payload
     * @throws GuzzleException
     * @throws Exception
     */
    public function createCustomer(Customer $customer): Customer
    {
        $response = $this->httpClient->post(
            uri: '/api/v1/customers',
            options: [
                'json' => [
                    'customer' => [
                        'external_id' => $customer->userGuid,

                        'name' => $customer->name,
                        'billing_configuration' => [
                            'payment_provider' => $customer->billingConfiguration->paymentProvider->value,
                            'provider_payment_methods' => $customer->billingConfiguration->providerPaymentMethods,
                            'sync' => $customer->billingConfiguration->sync,
                            'sync_with_provider' => $customer->billingConfiguration->syncWithProvider,
                        ]
                    ]
                ]
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to create subscription");
        }

        $payload = json_decode($response->getBody()->getContents());

        return new Customer(
            userGuid: (int) $payload->customer->external_id,
            lagoCustomerId: $payload->customer->lago_id,
            name: $payload->customer->name,
            createdAt: strtotime($payload->customer->created_at),
            billingConfiguration: !$payload->customer->billing_configuration->payment_provider
                ? null
                : new BillingConfiguration(
                    paymentProvider: PaymentProviderEnum::tryFrom($payload->customer->billing_configuration->payment_provider ?? ""),
                    invoiceGracePeriod: $payload->customer->billing_configuration->invoice_grace_period,
                    providerCustomerId: $payload->customer->billing_configuration->provider_customer_id,
                    sync: $payload->customer->billing_configuration->sync ?? false,
                    syncWithProvider: $payload->customer->billing_configuration->sync_with_provider,
                    providerPaymentMethods: $payload->customer->billing_configuration->provider_payment_methods,
                ),
            updatedAt: strtotime($payload->customer->updated_at),
            email: $payload->customer->email,
        );
    }
}
