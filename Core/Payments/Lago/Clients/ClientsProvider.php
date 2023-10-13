<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Clients;

use GuzzleHttp\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;

class ClientsProvider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(CustomersClient::class, function (Di $di): CustomersClient {
            return new CustomersClient(
                $this->getHttpClient()
            );
        });
        $this->di->bind(PlansClient::class, function (Di $di): PlansClient {
            return new PlansClient(
                $this->getHttpClient()
            );
        });
        $this->di->bind(SubscriptionsClient::class, function (Di $di): SubscriptionsClient {
            return new SubscriptionsClient(
                $this->getHttpClient()
            );
        });
    }

    private function getHttpClient(): Client
    {
        return new Client([
            'base_uri' => $this->di->get('Config')->get('payments')['lago']['base_url'],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->di->get('Config')->get('payments')['lago']['api_key'],
                'Content-Type' => 'application/json'
            ]
        ]);
    }
}
