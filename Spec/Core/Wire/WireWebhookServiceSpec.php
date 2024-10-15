<?php

namespace Spec\Minds\Core\Wire;

use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Core\Security\ACL;
use Minds\Core\Wire\WireWebhookService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Stripe\Event;
use Stripe\Invoice;
use Stripe\Plan;
use Stripe\StripeObject;
use Stripe\Subscription;

class WireWebhookServiceSpec extends ObjectBehavior
{
    private Collaborator $stripeSubscriptionsServiceMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $saveMock;
    private Collaborator $configMock;
    private Collaborator $aclMock;
    private Collaborator $stripeApiKeyConfigMock;
    private Collaborator $giftCardsManagerMock;

    
    function let (
        SubscriptionsService $stripeSubscriptionsServiceMock,
        EntitiesBuilder $entitiesBuilderMock,
        Save $saveMock,
        Config $configMock,
        ACL $aclMock,
        StripeApiKeyConfig $stripeApiKeyConfigMock,
        GiftCardsManager $giftCardsManagerMock,
    ) {
        $this->beConstructedWith(
            $stripeSubscriptionsServiceMock,
            $entitiesBuilderMock,
            $saveMock,
            $configMock,
            $aclMock,
            $stripeApiKeyConfigMock,
            $giftCardsManagerMock
        );

        $this->stripeSubscriptionsServiceMock = $stripeSubscriptionsServiceMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->saveMock = $saveMock;
        $this->configMock = $configMock;
        $this->aclMock = $aclMock;
        $this->stripeApiKeyConfigMock = $stripeApiKeyConfigMock;
        $this->giftCardsManagerMock = $giftCardsManagerMock;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(WireWebhookService::class);
    }

    function it_should_issue_minds_plus_and_a_gift_card(User $purchasingUser)
    {
        $invoice = new Invoice();
        $invoice->subscription = 'sub_id';
        $invoice->amount_due = 700;
        
        $event = new Event();
        $event->type = 'invoice.paid';
        $event->data = (object) [
            'object' => $invoice
        ];

        $plan = new Plan();
        $plan->product = 'prod_plus';

        $subscription = new Subscription('sub_id');
        $subscription->plan = $plan;
        $subscription->metadata = new StripeObject();
        $subscription->metadata->user_guid = '123';
        $subscription->current_period_end = 1728981499;

        $this->stripeSubscriptionsServiceMock->retrieveSubscription('sub_id')
            ->shouldBeCalled()
            ->willReturn($subscription);

        $this->entitiesBuilderMock->single('123')
            ->shouldBeCalled()
            ->willReturn($purchasingUser);

        $this->stripeApiKeyConfigMock->shouldUseTestMode(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn(false);

        $this->configMock->get('upgrades')
            ->shouldBeCalled()
            ->willReturn([
                'plus' => [
                    'stripe_product_id' => 'prod_plus'
                ],
                'pro' => [
                    'stripe_product_id' => 'prod_pro'
                ]
            ]);
        
        $this->configMock->get('plus')
            ->willReturn([
                'handler' => '456'
            ]);
        
        $purchasingUser->setPlusExpires(1728981499)
            ->shouldBeCalled();

        $this->saveMock->setEntity(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn($this->saveMock);
        $this->saveMock->withMutatedAttributes(['plus_expires'])
            ->shouldBeCalled()
            ->willReturn($this->saveMock);
        $this->saveMock->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilderMock->single('456')
            ->shouldBeCalled()
            ->willReturn(new User);

        $this->giftCardsManagerMock->issueMindsPlusAndProGiftCards(
            Argument::type(User::class),
            $purchasingUser,
            7.00,
            1728981499
        )
            ->shouldBeCalled();

        $this->onWebhookEvent($event);
    }

    function it_should_issue_minds_pro_and_a_gift_card(User $purchasingUser)
    {
        $invoice = new Invoice();
        $invoice->subscription = 'sub_id';
        $invoice->amount_due = 5000;
        
        $event = new Event();
        $event->type = 'invoice.paid';
        $event->data = (object) [
            'object' => $invoice
        ];

        $plan = new Plan();
        $plan->product = 'prod_pro';

        $subscription = new Subscription('sub_id');
        $subscription->plan = $plan;
        $subscription->metadata = new StripeObject();
        $subscription->metadata->user_guid = '123';
        $subscription->current_period_end = 1728981499;

        $this->stripeSubscriptionsServiceMock->retrieveSubscription('sub_id')
            ->shouldBeCalled()
            ->willReturn($subscription);

        $this->entitiesBuilderMock->single('123')
            ->shouldBeCalled()
            ->willReturn($purchasingUser);

        $this->stripeApiKeyConfigMock->shouldUseTestMode(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn(false);

        $this->configMock->get('upgrades')
            ->shouldBeCalled()
            ->willReturn([
                'plus' => [
                    'stripe_product_id' => 'prod_plus'
                ],
                'pro' => [
                    'stripe_product_id' => 'prod_pro'
                ]
            ]);
        
        $this->configMock->get('pro')
            ->willReturn([
                'handler' => '456'
            ]);
        
        $purchasingUser->setProExpires(1728981499)
            ->shouldBeCalled();

        $this->saveMock->setEntity(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn($this->saveMock);
        $this->saveMock->withMutatedAttributes(['pro_expires'])
            ->shouldBeCalled()
            ->willReturn($this->saveMock);
        $this->saveMock->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->entitiesBuilderMock->single('456')
            ->shouldBeCalled()
            ->willReturn(new User);

        $this->giftCardsManagerMock->issueMindsPlusAndProGiftCards(
            Argument::type(User::class),
            $purchasingUser,
            50.00,
            1728981499
        )
            ->shouldBeCalled();

        $this->onWebhookEvent($event);
    }
}
