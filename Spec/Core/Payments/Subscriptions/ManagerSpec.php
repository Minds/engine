<?php

namespace Spec\Minds\Core\Payments\Subscriptions;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Payments\Subscriptions\Delegates;
use Minds\Core\Payments\Subscriptions\Repository;
use Minds\Core\Payments\Subscriptions\Subscription;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Core\Payments\Stripe\Customers\ManagerV2 as CustomersManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Stripe\Customer;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var Delegates\AnalyticsDelegate */
    protected $analyticsDelegate;

    /** @var Delegates\EmailDelegate */
    protected $emailDelegate;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    private Collaborator $customersManagerMock;
    private Collaborator $subscriptionsServiceMock;

    public function let(
        Repository $repository,
        Delegates\AnalyticsDelegate $analyticsDelegate,
        Delegates\EmailDelegate $emailDelegate,
        EntitiesBuilder $entitiesBuilder,
        CustomersManager $customersManagerMock,
        SubscriptionsService $subscriptionsServiceMock,
    ) {
        $this->repository = $repository;
        $this->analyticsDelegate = $analyticsDelegate;
        $this->emailDelegate = $emailDelegate;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->customersManagerMock = $customersManagerMock;
        $this->subscriptionsServiceMock = $subscriptionsServiceMock;

        $this->beConstructedWith($repository, $analyticsDelegate, $emailDelegate, $entitiesBuilder, $customersManagerMock, $subscriptionsServiceMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Payments\Subscriptions\Manager');
    }

    public function it_should_charge_a_subscription(Subscription $subscription)
    {
        $sender = new User(123);
        $subscription->user = $sender;

        $recipient = new User(123);
        $subscription->getEntity()->willReturn($recipient);

        $this->setSubscription($subscription);

        $this->entitiesBuilder->single(Argument::any(), Argument::any())
            ->shouldBeCalled()
            ->willReturn($sender);

        $subscription->getId()
            ->shouldBecalled()
            ->willReturn('msubs_test');

        $subscription->getUser()
            ->shouldBeCalled()
            ->willReturn($sender);

        $subscription->getNextBilling()
            ->shouldBeCalled()
            ->willReturn(time());
        
        $subscription->getPlanId()
            ->shouldBeCalled()
            ->willReturn('spec');

        $subscription->getTrialDays()
            ->willReturn(null);

        $subscription->setTrialDays(0)
            ->willReturn($subscription);

        $subscription->setStatus('active')
            ->shouldBeCalled();
        
        $this->repository->get('msubs_test')
            ->shouldBeCalledOnce()
            ->willReturn($subscription);

        $this->repository->add($subscription)
            ->shouldBeCalled();

        Dispatcher::register('subscriptions:process', 'spec', function ($event) {
            return $event->setResponse(true);
        });

        $subscription->setLastBilling(time())
            ->shouldBeCalled();

        $subscription->getLastBilling()
            ->shouldBeCalled()
            ->willReturn(time());

        $subscription->getInterval()
            ->shouldBeCalled()
            ->willReturn('monthly');

        $subscription->setNextBilling(strtotime('+1 month', time()))
            ->shouldBeCalled();

        $this->analyticsDelegate->onCharge($subscription)
            ->shouldBeCalled();

        $this->charge()->shouldReturn(true);
    }

    public function it_should_charge_a_chargeable_sender(
        Subscription $subscription,
        User $user
    ) {
        $user->get('guid')->willReturn(123);

        $user->isBanned()->shouldBeCalled()->willReturn(false);
        $user->getDeleted()->shouldBeCalled()->willReturn(0);
        $user->get('enabled')->willReturn('yes');

        $subscription->user = $user;

        $this->setSubscription($subscription);

        $this->entitiesBuilder->single(Argument::any(), [
            'cache' => false,
        ])
            ->shouldBeCalled()
            ->willReturn($user);

        $this->canTransact('sender')->shouldReturn(true);
    }

    public function it_should_not_charge_a_disabled_sender_account(
        Subscription $subscription,
        User $user
    ) {
        $user->get('guid')->willReturn(123);

        $user->get('enabled')->willReturn('no');

        $subscription->user = $user;

        $this->setSubscription($subscription);

        $this->entitiesBuilder->single(Argument::any(), [
            'cache' => false,
        ])
            ->shouldBeCalled()
            ->willReturn($user);

        $this->canTransact('sender')->shouldReturn(false);
    }

    public function it_should_not_charge_a_deleted_sender_account(
        Subscription $subscription,
        User $user
    ) {
        $user->get('guid')->willReturn(123);

        $user->get('enabled')->willReturn('no');
        $user->isBanned()->willReturn(false);
        $user->getDeleted()->willReturn(0);


        $subscription->user = $user;

        $this->setSubscription($subscription);

        $this->entitiesBuilder->single(Argument::any(), [
            'cache' => false,
        ])
            ->shouldBeCalled()
            ->willReturn($user);

        $this->canTransact('sender')->shouldReturn(false);
    }

    public function it_should_not_charge_a_banned_sender_account(
        Subscription $subscription,
        User $user
    ) {
        $user->get('guid')->willReturn(123);

        $user->get('enabled')->shouldBeCalled()->willReturn('yes');
        $user->isBanned()->shouldBeCalled()->willReturn(true);

        $subscription->user = $user;

        $this->setSubscription($subscription);

        $this->entitiesBuilder->single(Argument::any(), [
            'cache' => false,
        ])
            ->shouldBeCalled()
            ->willReturn($user);

        $this->canTransact('sender')->shouldReturn(false);
    }

    public function it_should_create()
    {
        $user = new User;
        $user->guid = 123;

        $subscription = new Subscription;
        $subscription->setId('msubs_test')
            ->setPlanId('spec')
            ->setPaymentMethod('spec')
            ->setUser($user);

        $this->repository->get('msubs_test')
            ->shouldBeCalledOnce()
            ->willReturn($subscription);

        $this->repository->add($subscription)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->analyticsDelegate->onCreate($subscription)
            ->shouldBeCalled();

        $this->emailDelegate->onCreate($subscription)
            ->shouldBeCalled();

        $this->setSubscription($subscription);
        $this->create()
            ->shouldReturn(true);
    }

    public function it_should_create_with_trial_context()
    {
        $user = new User;
        $user->guid = 123;

        $subscription = new Subscription;
        $subscription->setId('msubs_test')
            ->setPlanId('spec')
            ->setPaymentMethod('spec')
            ->setUser($user)
            ->setLastBilling(1)
            ->setNextBilling(time() + 86400)
            ->setTrialDays(7);

        $this->repository->get('msubs_test')
            ->shouldBeCalledOnce()
            ->willReturn($subscription);

        // Will bill next in 7 days, as expected
        $this->repository->add(Argument::that(function ($subscription) {
            return $subscription->getNextBilling() === (86400 * 7) + 1;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->analyticsDelegate->onCreate($subscription)
            ->shouldBeCalled();

        $this->setSubscription($subscription);
        $this->create()
            ->shouldReturn(true);
    }

    public function it_should_calculate_the_next_billing_during_create()
    {
        $user = new User;
        $user->guid = 123;

        $subscription = new Subscription;
        $subscription->setId('msubs_test')
            ->setPlanId('spec')
            ->setPaymentMethod('spec')
            ->setInterval('daily')
            ->setLastBilling(time())
            ->setNextBilling(time() + 86400)
            ->setUser($user);

        $this->repository->get('msubs_test')
            ->shouldBeCalledOnce()
            ->willReturn($subscription);

        $this->repository->add($subscription)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setSubscription($subscription);
        $this->create()
            ->shouldReturn(true);
    }

    public function it_should_throw_if_not_valid()
    {
        $subscription = new Subscription;
        $subscription->setId('msubs_test');

        $this->setSubscription($subscription);
        $this->shouldThrow('\Exception')->duringCreate();
    }

    public function it_should_update()
    {
        $user = new User;
        $user->guid = 123;

        $subscription = new Subscription;
        $subscription->setId('msubs_test')
            ->setPlanId('spec')
            ->setPaymentMethod('spec')
            ->setUser($user);

        $this->repository->add($subscription)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setSubscription($subscription);
        $this->update()
            ->shouldReturn(true);
    }

    public function it_should_cancel()
    {
        $user = new User;
        $user->guid = 123;

        $subscription = new Subscription;
        $subscription->setId('msubs_test')
            ->setPlanId('spec')
            ->setPaymentMethod('spec')
            ->setInterval('daily')
            ->setUser($user);

        $this->repository->delete($subscription)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setSubscription($subscription);
        $this->cancel()->shouldReturn(true);
    }

    public function it_should_throw_if_no_type_during_cancel()
    {
        $subscription = new Subscription;
        $subscription->setId('msubs_test');

        $this->setSubscription($subscription);
        $this->shouldThrow('\Exception')->duringCancel();
    }

    public function it_should_get_next_billing_for_daily_recurring()
    {
        $subscription = new Subscription;
        $subscription->setInterval('daily');
        $subscription->setLastBilling(strtotime('2000-01-01T12:00:00+00:00'));
        $next = strtotime('+1 day', $subscription->getLastBilling());

        $this->setSubscription($subscription);
        $this->getNextBilling()
            ->shouldReturn($next);
    }

    public function it_should_get_next_billing_for_monthly_recurring()
    {
        $subscription = new Subscription;
        $subscription->setInterval('monthly');
        $subscription->setLastBilling(strtotime('2000-01-01T12:00:00+00:00'));
        $next = strtotime('+1 month', $subscription->getLastBilling());

        $this->setSubscription($subscription);
        $this->getNextBilling()
            ->shouldReturn($next);
    }

    public function it_should_get_next_billing_for_yearly_recurring()
    {
        $subscription = new Subscription;
        $subscription->setInterval('yearly');
        $subscription->setLastBilling(strtotime('2000-01-01T12:00:00+00:00'));
        $next = strtotime('+1 year', $subscription->getLastBilling());

        $this->setSubscription($subscription);
        $this->getNextBilling()
            ->shouldReturn($next);
    }

    public function it_should_return_false_if_cancelling_all_subscriptions_with_no_user_set()
    {
        $this->cancelAllSubscriptions()->shouldReturn(false);
    }

    public function it_should_cancel_all_subscriptions_from_and_to_a_user(User $user)
    {
        $sub = new Subscription();
        $sub->setId('1')
            ->setPlanId('wire')
            ->setPaymentMethod('money')
            ->setUser('1234')
            ->setEntity('4567')
            ->setStatus('active');
        $ownSubs[] = $sub;

        $sub = new Subscription();
        $sub->setId('2')
            ->setPlanId('wire')
            ->setPaymentMethod('tokens')
            ->setEntity('1234')
            ->setUser('4567')
            ->setStatus('active');
        $othersSubs[] = $sub;

        $sub = new Subscription();
        $sub->setId('3')
            ->setPlanId('wire')
            ->setPaymentMethod('tokens')
            ->setEntity('1234')
            ->setUser('891011')
            ->setStatus('active');
        $othersSubs[] = $sub;

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn('1234');

        $this->repository->getList(['user_guid' => '1234'])
            ->shouldBeCalled()
            ->willReturn($ownSubs);

        $this->repository->getList(['entity_guid' => '1234', 'status' => 'active'])
            ->shouldBeCalled()
            ->willReturn($othersSubs);

        $this->repository->delete(Argument::any())
            ->shouldBeCalledTimes(count($ownSubs) + count($othersSubs))
            ->willReturn(true);

        $this->setUser($user);
        $this->cancelAllSubscriptions()->shouldReturn(true);
    }

    /*function it_should_get_next_billing_as_null_for_custom_recurring()
    {
        $last_billing = 10000000;

        $this
            ->getNextBilling($last_billing, 'custom')
            ->shouldReturn(null);
    }

    function it_should_get_next_billing_as_null_for_empty_last_billing()
    {
        $this
            ->getNextBilling(null, 'custom')
            ->shouldReturn(null);
    }

    function it_should_get_next_billing_converting_date_time_to_timestamp()
    {
        $last_billing = strtotime('2000-01-01T12:00:00+00:00');
        $next_billing = strtotime('+1 day', $last_billing);

        $this
            ->getNextBilling(new \DateTime("@{$last_billing}"), 'daily')
            ->shouldReturn($next_billing);
    }

    function it_should_throw_if_invalid_recurring_during_get_next_billing()
    {
        $last_billing = 10000000;

        $this
            ->shouldThrow(new \Exception('Invalid recurring value'))
            ->duringGetNextBilling($last_billing, '^}invalid-recurring-value');
    }*/

    public function it_should_cancel_subscriptions()
    {
        $subscription = new Subscription();
        $subscription->setUserGuid("123")
            ->setEntity((new User())->set("guid", "456"));

        $this->repository->getList([
            'user_guid' => "123"
        ])
            ->shouldBeCalled()
            ->willReturn([ $subscription ]);

        $this->repository->delete($subscription)
            ->shouldBeCalled();

        $this->cancelSubscriptions("123", "456");
    }

    public function it_should_return_stripe_subscriptions_too()
    {
        $this->repository->getList(Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn([]);

        $this->customersManagerMock->getByUser(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn(new Customer('cus_test'));

        $sub1 = new \Stripe\Subscription('sub_1');
        $sub1->plan = new \Stripe\Plan();

        $sub2 = new \Stripe\Subscription('sub_1');
        $sub2->plan = new \Stripe\Plan();

        $response = new \Stripe\Collection();
        $response->refreshFrom([
            'data' => [
                $sub1,
                $sub2
            ]
        ], []);

        $this->subscriptionsServiceMock->getSubscriptions(
            'cus_test'
        )->willReturn($response);

        $list = $this->getList([
            'user' => new User(),
        ]);
        $list->shouldHaveCount(2);
    }

    public function it_should_cancel_a_stripe_subscription()
    {
        $subscription = new Subscription();

        $subscription->setId('sub_test');

        $this->subscriptionsServiceMock->cancelSubscription('sub_test')
            ->shouldBeCalled();

        $this->setSubscription($subscription)->cancel();
    }
}
