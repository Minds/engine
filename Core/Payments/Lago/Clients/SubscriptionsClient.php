<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Clients;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Payments\Lago\Enums\SubscriptionBillingTimeEnum;
use Minds\Core\Payments\Lago\Enums\SubscriptionStatusEnum;
use Minds\Core\Payments\Lago\Types\Subscription;

class SubscriptionsClient extends ApiClient
{
    public function __construct(
        HttpClient $httpClient
    ) {
        parent::__construct($httpClient);
    }

    /**
     * @param Subscription $subscription
     * @return Subscription
     * @throws GuzzleException
     * @throws Exception
     */
    public function createSubscription(Subscription $subscription): Subscription
    {
        $response = $this->httpClient->post(
            uri: '/api/v1/subscriptions',
            options: [
                'json' => [
                    'subscription' => [
                        'external_id' => $subscription->mindsSubscriptionId,
                        'external_customer_id' => $subscription->mindsCustomerId,
                        'plan_code' => $subscription->planCodeId,
                        'billing_time' => $subscription->billingTime->value,

                        'name' => $subscription->name,
                    ]
                ]
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to create subscription");
        }

        $payload = json_decode($response->getBody()->getContents());

        return new Subscription(
            billingTime: SubscriptionBillingTimeEnum::tryFrom($payload->subscription->billing_time),
            mindsCustomerId: (int) $payload->subscription->external_customer_id,
            mindsSubscriptionId: $payload->subscription->external_id,
            planCodeId: $payload->subscription->plan_code,
            status: SubscriptionStatusEnum::tryFrom($payload->subscription->status),
            name: $payload->subscription->name,
            startedAt: strtotime($payload->subscription->started_at),
            endingAt: !$payload->subscription->ending_at ? null : strtotime($payload->subscription->ending_at),
        );
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param int|null $mindsCustomerId
     * @param string|null $planCodeId
     * @param SubscriptionStatusEnum|null $status
     * @return iterable
     * @throws GuzzleException
     * @throws Exception
     */
    public function getSubscriptions(
        int $page = 1,
        int $perPage = 12,
        ?int $mindsCustomerId = null,
        ?string $planCodeId = null,
        ?SubscriptionStatusEnum $status = null,
    ): iterable {
        $params = [
            'page' => $page,
            'per_page' => $perPage,
        ];

        if ($mindsCustomerId) {
            $params['external_customer_id'] = $mindsCustomerId;
        }
        if ($planCodeId) {
            $params['plan_code'] = $planCodeId;
        }
        if ($status) {
            $params['status'] = $status->value;
        }

        $response = $this->httpClient->get(
            uri: "/api/v1/subscriptions",
            options: [
                'query' => $params
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to get subscriptions");
        }

        $payload = json_decode($response->getBody()->getContents());

        foreach ($payload->subscriptions as $subscription) {
            yield new Subscription(
                billingTime: SubscriptionBillingTimeEnum::tryFrom($subscription->billing_time),
                mindsCustomerId: (int) $subscription->external_customer_id,
                mindsSubscriptionId: $subscription->external_id,
                planCodeId: $subscription->plan_code,
                status: SubscriptionStatusEnum::tryFrom($subscription->status),
                name: $subscription->name,
                startedAt: strtotime($subscription->started_at),
                endingAt: !$subscription->ending_at ? null : strtotime($subscription->ending_at),
            );
        }
    }
}
