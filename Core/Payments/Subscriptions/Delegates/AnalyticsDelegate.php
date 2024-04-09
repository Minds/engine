<?php
namespace Minds\Core\Payments\Subscriptions\Delegates;

use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Subscriptions\Subscription;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;

class AnalyticsDelegate
{
    public function __construct(
        private ?PostHogService $postHogService = null,
    ) {
        $this->postHogService ??= Di::_()->get(PostHogService::class);
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    public function onCharge(Subscription $subscription): void
    {
        $this->emit('charge', $subscription);
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    public function onCreate(Subscription $subscription): void
    {
        $this->emit('create', $subscription);
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    public function onCancel(Subscription $subscription): void
    {
        $this->emit('cancel', $subscription);
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    private function emit(string $event, Subscription $subscription): void
    {
        if ($subscription->getPaymentMethod() === 'tokens') {
            $amount = BigNumber::fromPlain($subscription->getAmount(), '18')->toDouble();
        } else {
            $amount = $subscription->getAmount();
        }

        $properties = array_filter([
            'subscription_plan_id' => $subscription->getPlanId(),
            'subscription_payment_method' => $subscription->getPaymentMethod(),
            'entity_guid' => $subscription->getEntity() ? (string) $subscription->getEntity()->getGuid() : null,
            'subscription_id' => $subscription->getId(),
            'subscription_amount' => $amount,
            'subscription_interval' => $subscription->getInterval(),
            'subscription_last_billing' => date('c', $subscription->getLastBilling()),
            'subscription_next_billing' => date('c', $subscription->getNextBilling()),
            'subscription_status' => $subscription->getStatus(),
            'subscription_is_trial' => $subscription->getTrialDays() > 0,
        ]);

        $user = $subscription->getUser();

        if (!$user instanceof User) {
            return;
        }

        $this->postHogService->withUser($user)
            ->capture([
                'event' => 'user_subscription_' . $event,
                ...$properties
            ]);
    }
}
