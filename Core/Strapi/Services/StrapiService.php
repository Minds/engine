<?php
declare(strict_types=1);

namespace Minds\Core\Strapi\Services;

use GraphQL\Client as StrapiGraphQLClient;
use Minds\Core\Payments\Checkout\Enums\CheckoutPageKeyEnum;
use Minds\Core\Payments\Checkout\Types\AddOn;
use Minds\Core\Payments\Checkout\Types\CheckoutPage;
use Minds\Core\Payments\Checkout\Types\Plan;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class StrapiService
{
    public function __construct(
        private readonly StrapiGraphQLClient $client
    ) {
    }

    /**
     * @param string $planId
     * @return Plan
     * @throws GraphQLException
     */
    public function getPlan(string $planId): Plan
    {
        $query = <<<QUERY
            query {
              productPlans(
                filters: {
                  tier: {
                      eq: "$planId"
                  }
                },
                publicationState: LIVE
              ) {
                data {
                  id
                  attributes {
                    tier
                    title
                    subtitle
                    perksTitle
                    perks {
                      text
                    }
                  }
                }
              }
            }
        QUERY;

        $results = $this->client->runRawQuery($query);
        $results->reformatResults(true);
        $data = $results->getData()['productPlans']['data'];

        if(!count($data)) {
            throw new GraphQLException('Plan not found', 404);
        }

        $planDetails = $data[0]['attributes'];

        return new Plan(
            id: $planDetails['tier'],
            name: $planDetails['title'],
            description: $planDetails['subtitle'],
            perksTitle: $planDetails['perksTitle'],
            perks: array_map(fn ($perk) => $perk['text'], $planDetails['perks'])
        );
    }

    /**
     * @param array $addonIds
     * @return AddOn[]
     * @throws GraphQLException
     */
    public function getPlanAddons(array $addonIds): iterable
    {
        $query = <<<QUERY
            query (\$addons: [String!]){
              tenantAddOns (
                filters: {
                  key: {
                    in: \$addons
                  }
                },
                publicationState: LIVE
              ) {
                data {
                  attributes {
                    key
                    name
                    description
                    perksTitle
                    perks {
                      text
                    }
                  }
                }
              }
            }
        QUERY;

        $results = $this->client->runRawQuery(
            queryString: $query,
            resultsAsArray: true,
            variables: [
                'addons' => $addonIds
            ]
        );

        $data = $results->getData()['tenantAddOns']['data'];

        if (!count($data)) {
            return throw new GraphQLException('Addons not found', 404);
        }

        foreach ($data as $addon) {
            $addonDetails = $addon['attributes'];

            yield new AddOn(
                id: $addonDetails['key'],
                name: $addonDetails['name'],
                description: $addonDetails['description'],
                perksTitle: $addonDetails['perksTitle'],
                perks: array_map(fn ($perk) => $perk['text'], $addonDetails['perks'])
            );
        }
    }

    /**
     * @param CheckoutPageKeyEnum $page
     * @return CheckoutPage
     * @throws GraphQLException
     */
    public function getTenantCheckoutPage(CheckoutPageKeyEnum $page): CheckoutPage
    {
        $query = <<<QUERY
            query(\$pageKey: String!) {
              networksCheckoutPages(
                filters: {
                  key: {
                    eq: \$pageKey
                  }
                },
                publicationState: LIVE
              ) {
                data {
                  attributes {
                    key
                    description
                    title
                    termsMarkdown
                  }
                }
              }
            }
        QUERY;

        $results = $this->client->runRawQuery(
            queryString: $query,
            resultsAsArray: true,
            variables: [
                'pageKey' => $page->value
            ]
        );

        $data = $results->getData()['networksCheckoutPages']['data'];

        if (!count($data)) {
            return throw new GraphQLException('Checkout page not found', 404);
        }

        $planDetails = $data[0]['attributes'];

        return new CheckoutPage(
            id: $page,
            title: $planDetails['title'],
            description: $planDetails['description'],
            termsMarkdown: $planDetails['termsMarkdown'] ?? null
        );
    }
}
