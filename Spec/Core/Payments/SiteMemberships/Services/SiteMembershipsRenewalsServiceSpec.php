<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Log\Logger;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipSubscriptionFoundException;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipsRenewalsService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipSubscription;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService as StripeSubscriptionsService;
use Minds\Core\Payments\Stripe\Webhooks\Enums\WebhookEventTypeEnum;
use Minds\Core\Payments\Stripe\Webhooks\Model\SubscriptionsWebhookDetails;
use Minds\Core\Payments\Stripe\Webhooks\Services\SubscriptionsWebhookService;
use Minds\Exceptions\ServerErrorException;
use NotImplementedException;
use Prophecy\Argument;
use Stripe\Event as StripeEvent;
use Stripe\Invoice;
use Stripe\Subscription;

class SiteMembershipsRenewalsServiceSpec extends ObjectBehavior
{
    private Collaborator $subscriptionsWebhookServiceMock;
    private Collaborator $siteMembershipSubscriptionsServiceMock;
    private Collaborator $stripeSubscriptionsServiceMock;
    private Collaborator $loggerMock;

    public function let(
        SubscriptionsWebhookService $subscriptionsWebhookServiceMock,
        SiteMembershipSubscriptionsService $siteMembershipSubscriptionsServiceMock,
        StripeSubscriptionsService $stripeSubscriptionsServiceMock,
        Logger $loggerMock
    ): void {
        $this->subscriptionsWebhookServiceMock = $subscriptionsWebhookServiceMock;
        $this->siteMembershipSubscriptionsServiceMock = $siteMembershipSubscriptionsServiceMock;
        $this->stripeSubscriptionsServiceMock = $stripeSubscriptionsServiceMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith(
            $this->subscriptionsWebhookServiceMock,
            $this->siteMembershipSubscriptionsServiceMock,
            $this->stripeSubscriptionsServiceMock,
            $this->loggerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(SiteMembershipsRenewalsService::class);
    }

    public function it_should_process_subscription_renewal_event(StripeEvent $event): void
    {
        $payload = 'payload';
        $eventSignature = 'eventSignature';
        $stripeWebhookId = 'stripeWebhookId';
        $stripeWebhookSecret = 'stripeWebhookSecret';
        $currentPeriodStart = time();
        $currentPeriodEnd = strtotime('+1 month');

        $this->subscriptionsWebhookServiceMock
            ->getSubscriptionsWebhookDetails()
            ->willReturn(new SubscriptionsWebhookDetails(
                $stripeWebhookId,
                $stripeWebhookSecret
            ));

        $event = $this->buildStripeInvoiceEvent();

        $this->subscriptionsWebhookServiceMock->buildWebhookEvent(
            payload: $payload,
            signature: $eventSignature,
            secret: $stripeWebhookSecret
        )
            ->shouldBeCalled()
            ->willReturn($event);

        $this->siteMembershipSubscriptionsServiceMock
            ->getSiteMembershipSubscriptionByStripeSubscriptionId(
                stripeSubscriptionId: 'subscriptionId'
            )->shouldBeCalled();

        $this->stripeSubscriptionsServiceMock->retrieveSubscription(
            subscriptionId: 'subscriptionId'
        )
            ->shouldBeCalled()
            ->willReturn(
                $this->buildStripeSubscriptionDetails(
                    $currentPeriodStart,
                    $currentPeriodEnd
                )
            );

        $this->siteMembershipSubscriptionsServiceMock->renewSiteMembershipSubscription(
            stripeSubscriptionId: '1',
            startTimestamp: $currentPeriodStart,
            endTimestamp: $currentPeriodEnd
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->processSubscriptionRenewalEvent($payload, $eventSignature);
    }

    public function it_should_NOT_process_subscription_renewal_event_when_no_matching_subscription_is_found_in_the_database(StripeEvent $event): void
    {
        $payload = 'payload';
        $eventSignature = 'eventSignature';
        $stripeWebhookId = 'stripeWebhookId';
        $stripeWebhookSecret = 'stripeWebhookSecret';
        $currentPeriodStart = time();
        $currentPeriodEnd = strtotime('+1 month');

        $this->subscriptionsWebhookServiceMock
            ->getSubscriptionsWebhookDetails()
            ->willReturn(new SubscriptionsWebhookDetails(
                $stripeWebhookId,
                $stripeWebhookSecret
            ));

        $event = $this->buildStripeInvoiceEvent();

        $this->subscriptionsWebhookServiceMock->buildWebhookEvent(
            payload: $payload,
            signature: $eventSignature,
            secret: $stripeWebhookSecret
        )
            ->shouldBeCalled()
            ->willReturn($event);

        $this->siteMembershipSubscriptionsServiceMock
            ->getSiteMembershipSubscriptionByStripeSubscriptionId(
                stripeSubscriptionId: 'subscriptionId'
            )->shouldBeCalled()
            ->willThrow(new NoSiteMembershipSubscriptionFoundException(
                message: "No site membership subscription found for invoice 1"
            ));

        $this->stripeSubscriptionsServiceMock->retrieveSubscription(
            subscriptionId: 'subscriptionId'
        )
            ->shouldNotBeCalled();

        $this->siteMembershipSubscriptionsServiceMock->renewSiteMembershipSubscription(
            stripeSubscriptionId: '1',
            startTimestamp: $currentPeriodStart,
            endTimestamp: $currentPeriodEnd
        )
            ->shouldNotBeCalled();

        $this->shouldThrow(ServerErrorException::class)->duringProcessSubscriptionRenewalEvent($payload, $eventSignature);
    }

    public function it_should_handle_invoice_payment_failed(): void
    {
        $payload = 'payload';
        $eventSignature = 'eventSignature';
        $stripeWebhookId = 'stripeWebhookId';
        $stripeWebhookSecret = 'stripeWebhookSecret';

        $this->subscriptionsWebhookServiceMock
            ->getSubscriptionsWebhookDetails()
            ->willReturn(new SubscriptionsWebhookDetails(
                $stripeWebhookId,
                $stripeWebhookSecret
            ));

        $event = $this->buildStripeInvoiceEvent(
            WebhookEventTypeEnum::INVOICE_PAYMENT_FAILED
        );

        $this->subscriptionsWebhookServiceMock->buildWebhookEvent(
            payload: $payload,
            signature: $eventSignature,
            secret: $stripeWebhookSecret
        )
            ->shouldBeCalled()
            ->willReturn($event);

        $this->shouldThrow(
            new ServerErrorException(
                message: "Failed to build webhook event",
                previous: new NotImplementedException()
            )
        )->duringProcessSubscriptionRenewalEvent($payload, $eventSignature);
    }

    public function it_should_sync_site_memberships(): void
    {
        $tenantId = 123;
        $currentPeriodStart = time();
        $currentPeriodEnd = strtotime('+1 month');
        $siteMembershipMock = new SiteMembershipSubscription(
            membershipSubscriptionId: 1,
            membershipGuid: 1,
            stripeSubscriptionId: 'sub_stripeSubscriptionId',
            autoRenew: false,
            isManual: false
        );

        $this->siteMembershipSubscriptionsServiceMock->getAllSiteMemberships($tenantId)
            ->shouldBeCalled()
            ->willReturn([$siteMembershipMock]);

        $this->stripeSubscriptionsServiceMock->retrieveSubscription(
            subscriptionId: $siteMembershipMock->stripeSubscriptionId
        )
            ->shouldBeCalled()
            ->willReturn($this->buildStripeSubscriptionDetails(
                $currentPeriodStart,
                $currentPeriodEnd
            ));

        $this->siteMembershipSubscriptionsServiceMock->renewSiteMembershipSubscription(
            stripeSubscriptionId: '1',
            startTimestamp: $currentPeriodStart,
            endTimestamp: $currentPeriodEnd
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->syncSiteMemberships($tenantId);
    }

    public function it_should_sync_site_memberships_but_not_process_non_sub_payments(): void
    {
        $tenantId = 123;
        $siteMembershipMock = new SiteMembershipSubscription(
            membershipSubscriptionId: 1,
            membershipGuid: 1,
            stripeSubscriptionId: 'pi_stripeSubscriptionId',
            autoRenew: false,
            isManual: false
        );

        $this->siteMembershipSubscriptionsServiceMock->getAllSiteMemberships($tenantId)
            ->shouldBeCalled()
            ->willReturn([$siteMembershipMock]);

        $this->stripeSubscriptionsServiceMock->retrieveSubscription(
            subscriptionId: Argument::any()
        )
            ->shouldNotBeCalled();

        $this->siteMembershipSubscriptionsServiceMock->renewSiteMembershipSubscription(
            stripeSubscriptionId: Argument::any(),
            startTimestamp: Argument::any(),
            endTimestamp: Argument::any()
        )
            ->shouldNotBeCalled();

        $this->syncSiteMemberships($tenantId);
    }

    private function buildStripeInvoiceEvent(
        WebhookEventTypeEnum $type = WebhookEventTypeEnum::INVOICE_PAYMENT_SUCCEEDED
    ): StripeEvent {
        $event = new StripeEvent('1', []);
        $stripeInvoice = new Invoice('1', []);

        $stripeInvoice->__set('subscription', 'subscriptionId');
        $event->__set('type', $type->value);
        $event->__set('data', [ 'object' => $stripeInvoice ]);

        return $event;
    }

    private function buildStripeSubscriptionDetails(
        int $currentPeriodStart = 0,
        int $currentPeriodEnd = 0
    ): Subscription {
        $subscription = new Subscription('1', []);
        $subscription->__set('current_period_start', $currentPeriodStart);
        $subscription->__set('current_period_end', $currentPeriodEnd);
        return $subscription;
    }
}
