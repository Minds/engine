<?php
declare(strict_types=1);

namespace Minds\Core\Strapi;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\GraphQL\Client\Client as GraphQLClient;
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
                    client: $di->get(
                        GraphQLClient::class,
                        [
                            'base_uri' => $di->get('Config')->get('strapi')['url'] . "/graphql"
                        ]
                    ),
                    cache: $di->get('Cache')
                );
            }
        );
    }
}
