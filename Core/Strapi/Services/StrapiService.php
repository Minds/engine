<?php
declare(strict_types=1);

namespace Minds\Core\Strapi\Services;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\GraphQL\Client\Client as GraphQLClient;
use Minds\Core\GraphQL\Client\GraphQLQueryRequest;
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
        private readonly GraphQLClient  $client,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @param string $planId
     * @return Plan
     * @throws GraphQLException
     * @throws GuzzleException
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

        $request = (new GraphQLQueryRequest())
            ->setQuery($query);

        $results = $this->client->runQuery($request);
        $data = $results->toArray()['productPlans']['data'];

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
     * @throws GuzzleException
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

        $request = (new GraphQLQueryRequest())
            ->setQuery($query)
            ->setVariables(['addons' => array_diff($addonIds, $cachedItems)]);

        $results = $this->client->runQuery($request);

        $data = $results->toArray()['productAddOns']['data'];

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
     * @throws GuzzleException
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

        $request = (new GraphQLQueryRequest())
            ->setQuery($query)
            ->setVariables(['pageKey' => $page->value]);

        $results = $this->client->runQuery($request);

        $data = $results->toArray()['checkoutPages']['data'];

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
