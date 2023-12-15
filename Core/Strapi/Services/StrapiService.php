<?php
declare(strict_types=1);

namespace Minds\Core\Strapi\Services;

use GraphQL\Client as StrapiGraphQLClient;
use Minds\Core\Payments\Checkout\Enums\CheckoutPageKeyEnum;
use Minds\Core\Payments\Checkout\Types\AddOn;
use Minds\Core\Payments\Checkout\Types\CheckoutPage;
use Minds\Core\Payments\Checkout\Types\Plan;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class StrapiService
{
    private const CACHE_TTL = 60 * 30; // 30 minutes

    public function __construct(
        private readonly StrapiGraphQLClient $client,
        private readonly CacheInterface      $cache
    ) {
    }

    /**
     * @param string $planId
     * @return Plan
     * @throws GraphQLException
     * @throws InvalidArgumentException
     */
    public function getPlan(string $planId): Plan
    {
        if ($plan = $this->cache->get("strapi_product_plan_$planId")) {
            return unserialize($plan);
        }

        $query = <<<QUERY
            query {
              productPlans(
                filters: {
                  stripeProductKey: {
                      eq: "$planId"
                  }
                },
                publicationState: LIVE
              ) {
                data {
                  id
                  attributes {
                    tier
                    stripeProductKey
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

        if (!count($data)) {
            throw new GraphQLException('Plan not found', 404);
        }

        $planDetails = $data[0]['attributes'];

        $plan = new Plan(
            id: $planDetails['stripeProductKey'],
            name: $planDetails['title'],
            description: $planDetails['subtitle'],
            perksTitle: $planDetails['perksTitle'],
            perks: array_map(fn ($perk) => $perk['text'], $planDetails['perks'])
        );

        $this->cache->set("strapi_product_plan_$planId", serialize($plan), self::CACHE_TTL);

        return $plan;
    }

    /**
     * @param array $addonIds
     * @return AddOn[]
     * @throws GraphQLException
     * @throws InvalidArgumentException
     */
    public function getPlanAddons(array $addonIds): iterable
    {
        if (count($addonIds) === 0) {
            return [];
        }

        $cachedItems = [];
        foreach ($addonIds as $addonId) {
            if ($addon = $this->cache->get("strapi_product_addon_$addonId")) {
                $cachedItems[] = $addonId;
                yield unserialize($addon);
            }
        }

        if (count($cachedItems) === count($addonIds)) {
            return [];
        }

        $query = <<<QUERY
            query (\$addons: [String!]){
              productAddOns (
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
                'addons' => array_diff($addonIds, $cachedItems)
            ]
        );

        $data = $results->getData()['productAddOns']['data'];

        if (!count($data)) {
            return throw new GraphQLException('Addons not found', 404);
        }

        foreach ($data as $addon) {
            $addonDetails = $addon['attributes'];

            yield $addon = new AddOn(
                id: $addonDetails['key'],
                name: $addonDetails['name'],
                description: $addonDetails['description'],
                perksTitle: $addonDetails['perksTitle'],
                perks: $addonDetails['perks'] ? array_map(fn ($perk) => $perk['text'], $addonDetails['perks']) : null,
            );

            $this->cache->set("strapi_product_addon_{$addonDetails['key']}", serialize($addon), self::CACHE_TTL);
        }
    }

    /**
     * @param CheckoutPageKeyEnum $page
     * @return CheckoutPage
     * @throws GraphQLException
     * @throws InvalidArgumentException
     */
    public function getCheckoutPage(CheckoutPageKeyEnum $page): CheckoutPage
    {
        if ($cachedPage = $this->cache->get("strapi_checkout_page_{$page->value}")) {
            return unserialize($cachedPage);
        }

        $query = <<<QUERY
            query(\$pageKey: String!) {
              checkoutPages(
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

        $data = $results->getData()['checkoutPages']['data'];

        if (!count($data)) {
            return throw new GraphQLException('Checkout page not found', 404);
        }

        $planDetails = $data[0]['attributes'];

        $checkoutPage = new CheckoutPage(
            id: $page,
            title: $planDetails['title'],
            description: $planDetails['description'],
            termsMarkdown: $planDetails['termsMarkdown'] ?? null
        );

        $this->cache->set("strapi_checkout_page_{$page->value}", serialize($checkoutPage), self::CACHE_TTL);

        return $checkoutPage;
    }
}
