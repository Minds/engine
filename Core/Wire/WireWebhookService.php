<?php
namespace Minds\Core\Wire;

use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Core\Payments\Stripe\Webhooks\Enums\WebhookEventTypeEnum;
use Minds\Core\Payments\Stripe\Customers\ManagerV2 as CustomerManager;
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

        switch ($product) {
            case $this->config->get('upgrades')['plus']['stripe_product_id']:
                $user->setPlusExpires($stripeSubscription->current_period_end);

                $this->acl->setIgnore(true);
    
                $this->save
                    ->setEntity($user)
                    ->withMutatedAttributes([
                        'plus_expires',
                    ])
                    ->save();
                break;
            case $this->config->get('upgrades')['pro']['stripe_product_id']:
                $user->setProExpires($stripeSubscription->current_period_end);

                $this->acl->setIgnore(true);
    
                $this->save
                    ->setEntity($user)
                    ->withMutatedAttributes([
                        'pro_expires',
                    ])
                    ->save();
                break;
        }
    }
}
