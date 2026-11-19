<?php
declare(strict_types=1);

namespace Minds\Core\Channels\Delegates;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;

/**
 * Cancels a member's Minds+ and Pro Stripe subscriptions when their channel is deleted,
 * so a deleted account is no longer charged.
 *
 * Plus and Pro are Stripe subscriptions created by Wire\Manager with metadata user_guid,
 * so they are found by that metadata rather than by customer.
 */
class CancelPaidPlans
{
    /** Stripe statuses that can still charge the card. */
    private const BILLABLE_STATUSES = ['active', 'trialing', 'past_due', 'unpaid', 'incomplete'];

    public function __construct(
        private ?StripeClient $stripeClient = null,
        private ?Config $config = null,
    ) {
        $this->stripeClient ??= Di::_()->get(StripeClient::class, ['stripe_version' => '2020-08-27']);
        $this->config ??= Di::_()->get(Config::class);
    }

    /**
     * @return string[] ids of the subscriptions cancelled
     * @throws \Exception when Stripe could not be searched or a cancel failed
     */
    public function cancel(User $user): array
    {
        $userGuid = (string) $user->getGuid();
        if (!ctype_digit($userGuid)) {
            return [];
        }

        $productIds = $this->getPlanProductIds();
        if (!$productIds) {
            return [];
        }

        $client = $this->stripeClient->withUser($user);

        $results = $client->subscriptions->search([
            'query' => "metadata['user_guid']:'{$userGuid}'",
            'limit' => 100,
        ]);

        $cancelled = [];
        foreach ($results->autoPagingIterator() as $subscription) {
            if (($subscription->metadata['user_guid'] ?? null) !== $userGuid) {
                continue;
            }
            if (!in_array($subscription->status, self::BILLABLE_STATUSES, true)) {
                continue;
            }
            if (!$this->isPlanSubscription($subscription, $productIds)) {
                continue;
            }

            $client->subscriptions->cancel($subscription->id);
            $cancelled[] = $subscription->id;
        }

        return $cancelled;
    }

    /**
     * Live and test product ids for Plus and Pro.
     * @return string[]
     */
    private function getPlanProductIds(): array
    {
        $upgrades = $this->config->get('upgrades') ?: [];
        $ids = [];
        foreach (['plus', 'pro'] as $plan) {
            foreach (['stripe_product_id', 'stripe_product_id_test'] as $key) {
                if (!empty($upgrades[$plan][$key])) {
                    $ids[] = (string) $upgrades[$plan][$key];
                }
            }
        }
        return $ids;
    }

    private function isPlanSubscription($subscription, array $productIds): bool
    {
        foreach ($subscription->items->data ?? [] as $item) {
            $product = $item->price->product ?? null;
            $productId = is_string($product) ? $product : ($product->id ?? null);
            if ($productId && in_array($productId, $productIds, true)) {
                return true;
            }
        }
        return false;
    }
}
