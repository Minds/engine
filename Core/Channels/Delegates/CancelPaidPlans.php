<?php
declare(strict_types=1);

namespace Minds\Core\Channels\Delegates;

use Minds\Core\Config\Config;
use Minds\Core\Data\Cassandra\Thrift\Lookup;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Entities\User;
use Stripe\Exception\InvalidRequestException;
use Stripe\Service\SubscriptionService;

/**
 * Cancels a member's Minds+ and Pro Stripe subscriptions when their channel is deleted,
 * so a deleted account is no longer charged.
 *
 * Subscriptions are found two ways and merged:
 * - by the member's Stripe customer (lookup "<guid>:payments"), which is immediate and
 *   also finds subscriptions without user_guid metadata;
 * - by metadata user_guid (how Wire\Manager tags Plus/Pro), which finds plans on another
 *   customer but is eventually consistent (about a minute).
 *
 * Each cancel is tried on its own. Anything that fails is queued on ChannelDeferredOps as
 * "cancel_paid_plans" and retried up to MAX_ATTEMPTS times. The retry carries only ids and
 * the test-mode flag, so it works after the user entity is gone.
 *
 * Cancellation is immediate, with no proration or refund. Refunds stay a manual decision.
 */
class CancelPaidPlans
{
    public const STRIPE_VERSION = '2020-08-27';

    public const MAX_ATTEMPTS = 5;

    public const RETRY_TYPE = 'cancel_paid_plans';

    /** Stripe statuses that can still charge the card. */
    private const BILLABLE_STATUSES = ['active', 'trialing', 'past_due', 'unpaid', 'incomplete'];

    /** @var callable(bool): SubscriptionService */
    private $subscriptionsFactory;

    public function __construct(
        private ?StripeClient $stripeClient = null,
        private ?Config $config = null,
        private ?StripeApiKeyConfig $stripeApiKeyConfig = null,
        private ?Lookup $lookup = null,
        private ?QueueClient $queueClient = null,
        ?callable $subscriptionsFactory = null,
    ) {
        $this->stripeClient ??= Di::_()->get(StripeClient::class);
        $this->config ??= Di::_()->get(Config::class);
        $this->stripeApiKeyConfig ??= Di::_()->get(StripeApiKeyConfig::class);
        $this->lookup ??= Di::_()->get('Database\Cassandra\Lookup');
        $this->queueClient ??= Di::_()->get('Queue');
        $this->subscriptionsFactory = $subscriptionsFactory ?? fn (bool $testMode): SubscriptionService =>
            $this->stripeClient->withUser(null, [
                'api_key' => $this->getApiKey($testMode),
                'stripe_version' => self::STRIPE_VERSION,
            ])->subscriptions;
    }

    /**
     * Cancel the user's paid plans. Failures are queued for retry, never thrown.
     * @return array{cancelled: string[], failed: string[], lookup_failed: bool}
     */
    public function cancel(User $user): array
    {
        $userGuid = (string) $user->getGuid();
        if (!ctype_digit($userGuid)) {
            return $this->result();
        }

        $testMode = $this->stripeApiKeyConfig->shouldUseTestMode($user);
        [$customerId, $customerLookupFailed] = $this->getCustomerId($userGuid);

        $result = $this->run($userGuid, $customerId, $testMode, null);
        if ($customerLookupFailed) {
            $result['lookup_failed'] = true;
        }
        $this->queueRetryIfNeeded($userGuid, $customerId, $testMode, $result, 1);

        return $result;
    }

    /**
     * Handle a queued retry message.
     * @return array{cancelled: string[], failed: string[], lookup_failed: bool}
     */
    public function retry(array $message): array
    {
        $userGuid = (string) ($message['user_guid'] ?? '');
        if (!ctype_digit($userGuid)) {
            return $this->result();
        }

        $customerId = isset($message['customer_id']) ? (string) $message['customer_id'] : null;
        $testMode = (bool) ($message['test_mode'] ?? false);
        $attempt = (int) ($message['attempt'] ?? 1) + 1;
        $only = !empty($message['lookup_failed']) ? null : array_values($message['subscription_ids'] ?? []);

        // A first run that could not read the customer id tries again (the lookup row survives the channel delete).
        $customerLookupFailed = false;
        if ($customerId === null && $only === null) {
            [$customerId, $customerLookupFailed] = $this->getCustomerId($userGuid);
        }

        $result = $this->run($userGuid, $customerId, $testMode, $only);
        if ($customerLookupFailed) {
            $result['lookup_failed'] = true;
        }
        $this->queueRetryIfNeeded($userGuid, $customerId, $testMode, $result, $attempt);

        return $result;
    }

    /**
     * @param string[]|null $only retry just these subscription ids (null: look them all up)
     * @return array{cancelled: string[], failed: string[], lookup_failed: bool}
     */
    private function run(string $userGuid, ?string $customerId, bool $testMode, ?array $only): array
    {
        $result = $this->result();

        $productIds = $this->getPlanProductIds();
        if (!$productIds) {
            error_log("CHANNEL_DELETION_STRIPE_CANCEL_SKIPPED {$userGuid}: no Plus/Pro product ids configured");
            return $result;
        }

        try {
            $subscriptions = ($this->subscriptionsFactory)($testMode);
        } catch (\Throwable $e) {
            error_log("CHANNEL_DELETION_STRIPE_LOOKUP_FAILED {$userGuid} client: " . $e->getMessage());
            $result['lookup_failed'] = true;
            $result['failed'] = $only ?? [];
            return $result;
        }

        if ($only !== null) {
            $candidates = [];
            foreach ($only as $id) {
                try {
                    $candidates[] = $subscriptions->retrieve((string) $id);
                } catch (\Throwable $e) {
                    error_log("CHANNEL_DELETION_STRIPE_CANCEL_FAILED {$userGuid} {$id}: " . $e->getMessage());
                    $result['failed'][] = (string) $id;
                }
            }
        } else {
            $candidates = $this->findSubscriptions($subscriptions, $userGuid, $customerId, $result);
        }

        foreach ($candidates as $subscription) {
            if (!$this->belongsToUser($subscription, $userGuid, $customerId)
                || !in_array($subscription->status, self::BILLABLE_STATUSES, true)
                || !$this->isPlanSubscription($subscription, $productIds)
            ) {
                continue;
            }

            try {
                $subscriptions->cancel($subscription->id);
                $result['cancelled'][] = $subscription->id;
            } catch (\Throwable $e) {
                error_log("CHANNEL_DELETION_STRIPE_CANCEL_FAILED {$userGuid} {$subscription->id}: " . $e->getMessage());
                $result['failed'][] = $subscription->id;
            }
        }

        return $result;
    }

    /**
     * Customer subscriptions plus metadata search, de-duplicated by id.
     * Sets lookup_failed if either lookup could not be read.
     */
    private function findSubscriptions(SubscriptionService $subscriptions, string $userGuid, ?string $customerId, array &$result): array
    {
        $found = [];

        if ($customerId) {
            try {
                $list = $subscriptions->all(['customer' => $customerId, 'status' => 'all', 'limit' => 100]);
                foreach ($list->autoPagingIterator() as $subscription) {
                    $found[$subscription->id] = $subscription;
                }
            } catch (InvalidRequestException $e) {
                // The customer is not on this Stripe account (for example, it was made in the other mode). No retry
                // can read it; the metadata search below still runs.
                error_log("CHANNEL_DELETION_STRIPE_CUSTOMER_MISSING {$userGuid} {$customerId}: " . $e->getMessage());
            } catch (\Throwable $e) {
                error_log("CHANNEL_DELETION_STRIPE_LOOKUP_FAILED {$userGuid} customer: " . $e->getMessage());
                $result['lookup_failed'] = true;
            }
        }

        try {
            $search = $subscriptions->search([
                'query' => "metadata['user_guid']:'{$userGuid}'",
                'limit' => 100,
            ]);
            foreach ($search->autoPagingIterator() as $subscription) {
                $found[$subscription->id] ??= $subscription;
            }
        } catch (\Throwable $e) {
            error_log("CHANNEL_DELETION_STRIPE_LOOKUP_FAILED {$userGuid} search: " . $e->getMessage());
            $result['lookup_failed'] = true;
        }

        return array_values($found);
    }

    private function queueRetryIfNeeded(string $userGuid, ?string $customerId, bool $testMode, array $result, int $attempt): void
    {
        if (!$result['failed'] && !$result['lookup_failed']) {
            return;
        }

        if ($attempt >= self::MAX_ATTEMPTS) {
            error_log("CHANNEL_DELETION_STRIPE_CANCEL_GAVE_UP {$userGuid} customer=" . ($customerId ?? '-')
                . ' subscriptions=' . implode(',', $result['failed'])
                . ($result['lookup_failed'] ? ' lookup_failed' : ''));
            return;
        }

        try {
            $this->queueClient
                ->setQueue('ChannelDeferredOps')
                ->send([
                    'type' => self::RETRY_TYPE,
                    'user_guid' => $userGuid,
                    'customer_id' => $customerId,
                    'test_mode' => $testMode,
                    'subscription_ids' => $result['failed'],
                    'lookup_failed' => $result['lookup_failed'],
                    'attempt' => $attempt,
                ]);
        } catch (\Throwable $e) {
            error_log("CHANNEL_DELETION_STRIPE_CANCEL_GAVE_UP {$userGuid}: could not queue retry: " . $e->getMessage());
        }
    }

    private function belongsToUser($subscription, string $userGuid, ?string $customerId): bool
    {
        if (($subscription->metadata['user_guid'] ?? null) === $userGuid) {
            return true;
        }
        $customer = $subscription->customer ?? null;
        $subscriptionCustomerId = is_string($customer) ? $customer : ($customer->id ?? null);
        return $customerId !== null && $subscriptionCustomerId === $customerId;
    }

    /**
     * @return array{0: ?string, 1: bool} the member's Stripe customer id, and whether the lookup could not be read
     */
    private function getCustomerId(string $userGuid): array
    {
        try {
            $row = $this->lookup->get("{$userGuid}:payments");
            $customerId = $row['customer_id'] ?? null;
            return [$customerId ? (string) $customerId : null, false];
        } catch (\Throwable $e) {
            error_log("CHANNEL_DELETION_STRIPE_LOOKUP_FAILED {$userGuid} customer id: " . $e->getMessage());
            return [null, true];
        }
    }

    private function getApiKey(bool $testMode): string
    {
        $stripeConfig = $this->config->get('payments')['stripe'] ?? [];
        return (string) ($testMode ? ($stripeConfig['test_api_key'] ?? '') : ($stripeConfig['api_key'] ?? ''));
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

    private function result(): array
    {
        return ['cancelled' => [], 'failed' => [], 'lookup_failed' => false];
    }
}
