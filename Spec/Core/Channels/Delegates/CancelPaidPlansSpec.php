<?php

namespace Spec\Minds\Core\Channels\Delegates;

use Minds\Core\Channels\Delegates\CancelPaidPlans;
use Minds\Core\Config\Config;
use Minds\Core\Data\Cassandra\Thrift\Lookup;
use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Stripe\Collection;
use Stripe\SearchResult;
use Stripe\Service\SubscriptionService;
use Stripe\Subscription;

class CancelPaidPlansSpec extends ObjectBehavior
{
    private $subscriptions;
    private $config;
    private $apiKeyConfig;
    private $lookup;
    private $queueClient;
    private $factoryCalls;

    public function let(
        StripeClient $stripeClient,
        Config $config,
        StripeApiKeyConfig $apiKeyConfig,
        Lookup $lookup,
        QueueClient $queueClient,
        SubscriptionService $subscriptions
    ) {
        $this->subscriptions = $subscriptions;
        $this->config = $config;
        $this->apiKeyConfig = $apiKeyConfig;
        $this->lookup = $lookup;
        $this->queueClient = $queueClient;
        $this->factoryCalls = new \ArrayObject();

        $calls = $this->factoryCalls;
        $wrapped = $subscriptions->getWrappedObject();

        $config->get('upgrades')->willReturn([
            'plus' => ['stripe_product_id' => 'prod_plus', 'stripe_product_id_test' => 'prod_plus_test'],
            'pro' => ['stripe_product_id' => 'prod_pro'],
        ]);
        $apiKeyConfig->shouldUseTestMode(Argument::any())->willReturn(false);
        $lookup->get('1000:payments')->willReturn(['customer_id' => 'cus_1']);
        $queueClient->setQueue('ChannelDeferredOps')->willReturn($queueClient);

        $this->beConstructedWith(
            $stripeClient,
            $config,
            $apiKeyConfig,
            $lookup,
            $queueClient,
            function (bool $testMode) use ($calls, $wrapped) {
                $calls[] = $testMode;
                return $wrapped;
            }
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CancelPaidPlans::class);
    }

    public function it_does_nothing_for_an_invalid_guid(User $user)
    {
        $user->getGuid()->willReturn("1' OR '1");

        $this->subscriptions->search(Argument::any())->shouldNotBeCalled();
        $this->subscriptions->all(Argument::any())->shouldNotBeCalled();

        $this->cancel($user)->shouldReturn(['cancelled' => [], 'failed' => [], 'lookup_failed' => false]);
    }

    public function it_does_nothing_when_no_plan_products_are_configured(User $user)
    {
        $user->getGuid()->willReturn('1000');
        $this->config->get('upgrades')->willReturn(['plus' => [], 'pro' => []]);

        $this->subscriptions->search(Argument::any())->shouldNotBeCalled();

        $this->cancel($user)->shouldReturn(['cancelled' => [], 'failed' => [], 'lookup_failed' => false]);
    }

    public function it_cancels_plus_and_pro_found_by_customer_and_by_guid_and_skips_the_rest(User $user)
    {
        $user->getGuid()->willReturn('1000');

        $this->subscriptions->all(['customer' => 'cus_1', 'status' => 'all', 'limit' => 100])
            ->shouldBeCalled()
            ->willReturn($this->collection([
                // no metadata, only on the member's customer: still cancelled
                $this->sub('sub_nometa', 'active', 'prod_plus', null, 'cus_1'),
                // already cancelled: skipped
                $this->sub('sub_done', 'canceled', 'prod_plus', '1000', 'cus_1'),
                // not Plus/Pro: skipped
                $this->sub('sub_other', 'active', 'prod_networks', '1000', 'cus_1'),
            ]));

        $this->subscriptions->search([
            'query' => "metadata['user_guid']:'1000'",
            'limit' => 100,
        ])
            ->shouldBeCalled()
            ->willReturn($this->searchResult([
                // duplicate of a customer result: cancelled once
                $this->sub('sub_nometa', 'active', 'prod_plus', null, 'cus_1'),
                // Pro on another customer, tagged with the guid, past_due still bills
                $this->sub('sub_pro', 'past_due', 'prod_pro', '1000', 'cus_2'),
                // paused-style "unpaid" still bills
                $this->sub('sub_unpaid', 'unpaid', 'prod_plus_test', '1000', 'cus_2'),
                // a different member whose guid only looks similar: skipped
                $this->sub('sub_10001', 'active', 'prod_plus', '10001', 'cus_9'),
            ]));

        $this->subscriptions->cancel('sub_nometa')->shouldBeCalledTimes(1)->willReturn(null);
        $this->subscriptions->cancel('sub_pro')->shouldBeCalledTimes(1)->willReturn(null);
        $this->subscriptions->cancel('sub_unpaid')->shouldBeCalledTimes(1)->willReturn(null);
        $this->subscriptions->cancel('sub_done')->shouldNotBeCalled();
        $this->subscriptions->cancel('sub_other')->shouldNotBeCalled();
        $this->subscriptions->cancel('sub_10001')->shouldNotBeCalled();

        $this->queueClient->send(Argument::any())->shouldNotBeCalled();

        $this->cancel($user)->shouldReturn([
            'cancelled' => ['sub_nometa', 'sub_pro', 'sub_unpaid'],
            'failed' => [],
            'lookup_failed' => false,
        ]);
    }

    public function it_keeps_cancelling_after_one_cancel_fails_and_queues_the_failure(User $user)
    {
        $user->getGuid()->willReturn('1000');

        $this->subscriptions->all(Argument::any())->willReturn($this->collection([]));
        $this->subscriptions->search(Argument::any())->willReturn($this->searchResult([
            $this->sub('sub_plus', 'active', 'prod_plus', '1000', 'cus_1'),
            $this->sub('sub_pro', 'active', 'prod_pro', '1000', 'cus_1'),
        ]));

        $this->subscriptions->cancel('sub_plus')->willThrow(new \Exception('stripe 500'));
        $this->subscriptions->cancel('sub_pro')->shouldBeCalled()->willReturn(null);

        $this->queueClient->send([
            'type' => 'cancel_paid_plans',
            'user_guid' => '1000',
            'customer_id' => 'cus_1',
            'test_mode' => false,
            'subscription_ids' => ['sub_plus'],
            'lookup_failed' => false,
            'attempt' => 1,
        ])->shouldBeCalled();

        $this->cancel($user)->shouldReturn([
            'cancelled' => ['sub_pro'],
            'failed' => ['sub_plus'],
            'lookup_failed' => false,
        ]);
    }

    public function it_queues_a_full_retry_when_stripe_cannot_be_searched(User $user)
    {
        $user->getGuid()->willReturn('1000');

        $this->subscriptions->all(Argument::any())->willThrow(new \Exception('timeout'));
        $this->subscriptions->search(Argument::any())->willThrow(new \Exception('timeout'));

        $this->queueClient->send(Argument::that(fn ($m) => $m['lookup_failed'] === true && $m['attempt'] === 1))
            ->shouldBeCalled();

        $this->cancel($user)->shouldReturn(['cancelled' => [], 'failed' => [], 'lookup_failed' => true]);
    }

    public function it_queues_a_full_retry_when_the_customer_id_cannot_be_read(User $user)
    {
        $user->getGuid()->willReturn('1000');
        $this->lookup->get('1000:payments')->willThrow(new \Exception('cassandra down'));

        $this->subscriptions->all(Argument::any())->shouldNotBeCalled();
        $this->subscriptions->search(Argument::any())->willReturn($this->searchResult([]));

        $this->queueClient->send(Argument::that(fn ($m) => $m['lookup_failed'] === true && $m['customer_id'] === null))
            ->shouldBeCalled();

        $this->cancel($user)->shouldReturn(['cancelled' => [], 'failed' => [], 'lookup_failed' => true]);
    }

    public function it_reads_the_customer_id_again_on_a_full_retry()
    {
        $this->subscriptions->all(['customer' => 'cus_1', 'status' => 'all', 'limit' => 100])
            ->shouldBeCalled()
            ->willReturn($this->collection([$this->sub('sub_nometa', 'active', 'prod_plus', null, 'cus_1')]));
        $this->subscriptions->search(Argument::any())->willReturn($this->searchResult([]));
        $this->subscriptions->cancel('sub_nometa')->shouldBeCalled()->willReturn(null);
        $this->queueClient->send(Argument::any())->shouldNotBeCalled();

        $this->retry([
            'user_guid' => '1000',
            'customer_id' => null,
            'subscription_ids' => [],
            'lookup_failed' => true,
            'attempt' => 1,
        ])->shouldReturn(['cancelled' => ['sub_nometa'], 'failed' => [], 'lookup_failed' => false]);
    }

    public function it_does_not_retry_for_ever_on_a_customer_this_stripe_does_not_have(User $user)
    {
        $user->getGuid()->willReturn('1000');
        $this->subscriptions->all(Argument::any())
            ->willThrow(new \Stripe\Exception\InvalidRequestException('No such customer'));
        $this->subscriptions->search(Argument::any())->willReturn($this->searchResult([]));
        $this->queueClient->send(Argument::any())->shouldNotBeCalled();

        $this->cancel($user)->shouldReturn(['cancelled' => [], 'failed' => [], 'lookup_failed' => false]);
    }

    public function it_queues_a_retry_when_the_stripe_client_cannot_be_built(
        StripeClient $stripeClient,
        Config $config,
        StripeApiKeyConfig $apiKeyConfig,
        Lookup $lookup,
        QueueClient $queueClient,
        User $user
    ) {
        $this->beConstructedWith($stripeClient, $config, $apiKeyConfig, $lookup, $queueClient, function () {
            throw new \Exception('no key');
        });
        $user->getGuid()->willReturn('1000');
        $queueClient->send(Argument::that(fn ($m) => $m['lookup_failed'] === true))->shouldBeCalled();

        $this->cancel($user)->shouldReturn(['cancelled' => [], 'failed' => [], 'lookup_failed' => true]);
    }

    public function it_retries_only_the_failed_subscriptions(User $user)
    {
        $this->subscriptions->retrieve('sub_plus')
            ->willReturn($this->sub('sub_plus', 'active', 'prod_plus', '1000', 'cus_1'));
        $this->subscriptions->cancel('sub_plus')->shouldBeCalled()->willReturn(null);
        $this->subscriptions->search(Argument::any())->shouldNotBeCalled();
        $this->queueClient->send(Argument::any())->shouldNotBeCalled();

        $this->retry([
            'type' => 'cancel_paid_plans',
            'user_guid' => '1000',
            'customer_id' => 'cus_1',
            'test_mode' => true,
            'subscription_ids' => ['sub_plus'],
            'lookup_failed' => false,
            'attempt' => 1,
        ])->shouldReturn(['cancelled' => ['sub_plus'], 'failed' => [], 'lookup_failed' => false]);
    }

    public function it_gives_up_after_the_last_attempt(User $user)
    {
        $this->subscriptions->retrieve('sub_plus')
            ->willReturn($this->sub('sub_plus', 'active', 'prod_plus', '1000', 'cus_1'));
        $this->subscriptions->cancel('sub_plus')->willThrow(new \Exception('stripe 500'));
        $this->queueClient->send(Argument::any())->shouldNotBeCalled();

        $this->retry([
            'user_guid' => '1000',
            'customer_id' => 'cus_1',
            'subscription_ids' => ['sub_plus'],
            'attempt' => CancelPaidPlans::MAX_ATTEMPTS - 1,
        ])->shouldReturn(['cancelled' => [], 'failed' => ['sub_plus'], 'lookup_failed' => false]);
    }

    private function sub($id, $status, $product, $userGuid, $customer)
    {
        return Subscription::constructFrom([
            'id' => $id,
            'object' => 'subscription',
            'status' => $status,
            'customer' => $customer,
            'metadata' => $userGuid === null ? [] : ['user_guid' => $userGuid],
            'items' => [
                'object' => 'list',
                'data' => [['object' => 'subscription_item', 'id' => "si_{$id}", 'price' => ['object' => 'price', 'id' => "price_{$id}", 'product' => $product]]],
                'has_more' => false,
            ],
        ]);
    }

    private function collection($subs)
    {
        return Collection::constructFrom(['object' => 'list', 'data' => array_map(fn ($s) => $s->toArray(), $subs), 'has_more' => false]);
    }

    private function searchResult($subs)
    {
        return SearchResult::constructFrom(['object' => 'search_result', 'data' => array_map(fn ($s) => $s->toArray(), $subs), 'has_more' => false]);
    }
}
