<?php

namespace Spec\Minds\Core\Subscriptions;

use Minds\Core\Subscriptions\Repository;
use Minds\Core\Subscriptions\Subscription;
use Minds\Core\Data\Cassandra\Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;
use Minds\Common\Repository\Response;

class RepositorySpec extends ObjectBehavior
{
    private $client;

    public function let(Client $client)
    {
        $this->beConstructedWith($client);
        $this->client = $client;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add_a_subscription()
    {
        $this->client->batchRequest(Argument::that(function ($requests) {
            return $requests[0]['values'][0] == 123
                && $requests[0]['values'][1] == 456
                && $requests[1]['values'][0] == 456
                && $requests[1]['values'][1] == 123;
        }), 1)
            ->shouldBeCalled()
            ->willReturn(true);

        $subscription = new Subscription();
        $subscription->setSubscriberGuid(123);
        $subscription->setPublisherGuid(456);

        $newSubscription = $this->add($subscription);
        $newSubscription->isActive()
            ->shouldBe(true);
    }

    public function it_should_delete_a_subscription()
    {
        $this->client->batchRequest(Argument::that(function ($requests) {
            return $requests[0]['values'][0] == 123
                && $requests[0]['values'][1] == 456
                && $requests[1]['values'][0] == 456
                && $requests[1]['values'][1] == 123;
        }), 1)
            ->shouldBeCalled()
            ->willReturn(true);

        $subscription = new Subscription();
        $subscription->setSubscriberGuid(123);
        $subscription->setPublisherGuid(456);

        $newSubscription = $this->delete($subscription);
        $newSubscription->isActive()
            ->shouldBe(false);
    }

    public function it_should_get_subscribers()
    {
        $this->client->request(Argument::that(function ($prepared) {
            var_dump($prepared->getTemplate());

            return $prepared->getTemplate() === "SELECT * FROM friendsof WHERE key = ?";
        }))
            ->shouldBeCalled()
            ->willReturn(new Rows([
                [ 'guid' => 1 ],
                [ 'guid' => 2 ],
            ], 'paging-token'));

        $this->getList([
            'guid' => '1234567891',
            'type' => 'subscribers',
        ])->shouldImplement(Response::class);
    }

    public function it_should_get_subscriptions()
    {
        $this->client->request(Argument::that(function ($prepared) {
            return $prepared->getTemplate() === "SELECT * FROM friends WHERE key = ?";
        }))
            ->shouldBeCalled()
            ->willReturn(new Rows([
                [ 'guid' => 1 ],
                [ 'guid' => 2 ],
            ], 'paging-token'));

        $this->getList([
            'guid' => '1234567891',
            'type' => 'subscriptions',
        ])->shouldImplement(Response::class);
    }
}
