<?php
namespace Minds\Core\Wire;

use Minds\Common\SystemUser;
use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Core\Payments\Stripe\Webhooks\Enums\WebhookEventTypeEnum;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use Stripe\Event;
use Stripe\Invoice;
use Stripe\Plan;

class WireWebhookService
{
    public function __construct(
        private SubscriptionsService $stripeSubscriptionsService,
        private EntitiesBuilder $entitiesBuilder,
        private Save $save,
        private Config $config,
        private ACL $acl,
        private StripeApiKeyConfig $stripeApiKeyConfig,
        private GiftCardsManager $giftCardsManager,
    ) {
        
    }

    /**
     * Called via the Event.php file. Received in WebhookPsrController.php controller.
     */
    public function onWebhookEvent(Event $event): void
    {
        if ($event->type === WebhookEventTypeEnum::INVOICE_PAID->value) {
            // Is this invoice connected to a subscription?
            $this->handleSuccessfulInvoicePayment($event->data->object);

        }
    }

    /**
     * @throws ApiErrorException
     * @throws ServerErrorException
     */
    private function handleSuccessfulInvoicePayment(Invoice $invoice): void
    {
        $stripeSubscription = $this->stripeSubscriptionsService->retrieveSubscription(
            subscriptionId: $invoice->subscription
        );

        /** @var Plan */
        $plan = $stripeSubscription->plan;

        $product = $plan->product;

        /** @var User */
        $user = $this->entitiesBuilder->single($stripeSubscription->metadata->toArray()['user_guid']);

        // Is this a test account? If so we will use different product and price ids
        $isTestMode = false;
        if ($this->stripeApiKeyConfig->shouldUseTestMode($user)) {
            $isTestMode = true;
        }

        $plusProductId = $this->config->get('upgrades')['plus'][$isTestMode ? 'stripe_product_id_test' : 'stripe_product_id'];
        $proProductId = $this->config->get('upgrades')['pro'][$isTestMode ? 'stripe_product_id_test' : 'stripe_product_id'];

        $sender = null;
        switch ($product) {
            case $plusProductId:
                $user->setPlusExpires($stripeSubscription->current_period_end);

                $this->acl->setIgnore(true);
    
                $this->save
                    ->setEntity($user)
                    ->withMutatedAttributes([
                        'plus_expires',
                    ])
                    ->save();

                $sender = $this->entitiesBuilder->single($this->config->get('plus')['handler']);
                break;
            case $proProductId:
                $user->setProExpires($stripeSubscription->current_period_end);

                $this->acl->setIgnore(true);
    
                $this->save
                    ->setEntity($user)
                    ->withMutatedAttributes([
                        'pro_expires',
                    ])
                    ->save();

                $sender = $this->entitiesBuilder->single($this->config->get('pro')['handler']);
                break;
            default:
                return;
        }

        // If invoice is positive (ie. not a trial), issue a gift card
        if ($invoice->total_excluding_tax > 0) {
            $this->giftCardsManager->issueMindsPlusAndProGiftCards(
                sender: $sender ?? new SystemUser(),
                recipient: $user,
                amount: $invoice->total_excluding_tax / 100,
                expiryTimestamp: $stripeSubscription->current_period_end
            );
        }
    }
}
