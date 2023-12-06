<?php
declare(strict_types=1);

namespace Minds\Core\Strapi;

use GraphQL\Client as StrapiGraphQLClient;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Strapi\Services\StrapiService;

class Provider extends DiProvider
{
    /*
     *
     */
    public function register(): void
    {
        $this->di->bind(
            StrapiService::class,
            function (Di $di): StrapiService {
                return new StrapiService(
                    client: $di->get(StrapiGraphQLClient::class),
                );
            }
        );

        $this->di->bind(
            StrapiGraphQLClient::class,
            function (Di $di): StrapiGraphQLClient {
                $strapiUrl = $di->get('Config')->get('strapi')['url'];
                return new StrapiGraphQLClient(
                    endpointUrl: $strapiUrl . '/graphql',
                );
            }
        );
    }
}
