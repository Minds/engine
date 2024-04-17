<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Exception;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipSubscriptionFoundException;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService as StripeSubscriptionsService;
use Minds\Core\Payments\Stripe\Webhooks\Enums\WebhookEventTypeEnum;
use Minds\Core\Payments\Stripe\Webhooks\Services\SubscriptionsWebhookService;
use Minds\Exceptions\ServerErrorException;
use NotImplementedException;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice;

class SiteMembershipsRenewalsService
{
    public function __construct(
        private readonly SubscriptionsWebhookService $subscriptionsWebhookService,
        private readonly SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService,
        private readonly StripeSubscriptionsService $stripeSubscriptionsService,
    )
    {
    }

    /**
     * @param string $payload
     * @param string $eventSignature
     * @return void
     * @throws ServerErrorException
     */
    public function processSubscriptionRenewalEvent(
        string $payload,
        string $eventSignature,
    ): void
    {
        $webhookDetails = $this->subscriptionsWebhookService->getSubscriptionsWebhookDetails();

        try {
            $event = $this->subscriptionsWebhookService->buildWebhookEvent(
                payload: $payload,
                signature: $eventSignature,
                secret: $webhookDetails->stripeWebhookSecret
            );

            match (WebhookEventTypeEnum::tryFrom($event->type)) {
                WebhookEventTypeEnum::INVOICE_PAYMENT_SUCCEEDED => $this->handleSuccessfulInvoicePayment($event->data->object),
                WebhookEventTypeEnum::INVOICE_PAYMENT_FAILED => $this->handleFailedInvoicePayment($event->data->object),
                default => throw new ServerErrorException(
                    message: "Unhandled webhook event type: {$event->type}"
                )
            };
        } catch (Exception $e) {
            throw new ServerErrorException(
                message: "Failed to build webhook event",
                previous: $e
            );
        }
    }

    /**
     * @param Invoice $invoice
     * @return bool
     * @throws ApiErrorException
     * @throws ServerErrorException
     */
    private function handleSuccessfulInvoicePayment(Invoice $invoice): bool
    {
        try {
            $this->siteMembershipSubscriptionsService->getSiteMembershipSubscriptionByStripeSubscriptionId(
                stripeSubscriptionId: $invoice->subscription
            );
        } catch (NoSiteMembershipSubscriptionFoundException $e) {
            throw new ServerErrorException(
                message: "No site membership subscription found for invoice {$invoice->id}",
                previous: $e
            );
        }

        $stripeSubscription = $this->stripeSubscriptionsService->retrieveSubscription(
            subscriptionId: $invoice->subscription
        );

        return $this->siteMembershipSubscriptionsService->renewSiteMembershipSubscription(
            stripeSubscriptionId: $stripeSubscription->id,
            startTimestamp: $stripeSubscription->current_period_start,
            endTimestamp: $stripeSubscription->current_period_end
        );
    }

    /**
     * @param Invoice $invoice
     * @return bool
     * @throws NotImplementedException
     */
    private function handleFailedInvoicePayment(Invoice $invoice): bool
    {
        throw new NotImplementedException();
    }

    /**
     * Synchronize site memberships with Stripe
     * @param int|null $tenantId
     * @return void
     * @throws ApiErrorException
     * @throws ServerErrorException
     */
    public function syncSiteMemberships(
        ?int $tenantId = null
    ): void
    {
        $siteMembershipSubscriptions = $this->siteMembershipSubscriptionsService->getAllSiteMemberships($tenantId);

        foreach ($siteMembershipSubscriptions as $siteMembershipSubscription) {
            $stripeSubscription = $this->stripeSubscriptionsService->retrieveSubscription(
                subscriptionId: $siteMembershipSubscription->stripeSubscriptionId
            );

            $this->siteMembershipSubscriptionsService->renewSiteMembershipSubscription(
                stripeSubscriptionId: $stripeSubscription->id,
                startTimestamp: $stripeSubscription->current_period_start,
                endTimestamp: $stripeSubscription->current_period_end
            );
        }
    }
}
