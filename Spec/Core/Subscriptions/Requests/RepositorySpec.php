<?php

namespace Spec\Minds\Core\Subscriptions\Requests;

use Minds\Core\Subscriptions\Requests\Repository;
use Minds\Core\Subscriptions\Requests\SubscriptionRequest;
use Minds\Core\Data\Cassandra\Client;
use Spec\Minds\Mocks\Cassandra\Rows;
use Cassandra\Timestamp;
use Cassandra\Bigint;
use Cassandra\Boolean;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private $db;

    public function let(Client $db)
    {
        $this->beConstructedWith($db);
        $this->db = $db;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_a_request_from_a_urn()
    {
        $this->db->request(Argument::that(function ($prepared) {
            $query = $prepared->build();
            return $query['values'][0]->value() == '123'
                && $query['values'][1]->value() == '456';
        }))
            ->willReturn(new Rows([
                [
                    'publisher_guid' => '123',
                    'subscriber_guid' => '456',
                    'timestamp' => new Timestamp(time()),
                    'declined' => null,
                ]
                ], 'next-page-token'));

        $subscriptionRequest = $this->get("urn:subscription-request:123-456");
        $subscriptionRequest->getPublisherGuid()
            ->shouldBe('123');
        $subscriptionRequest->getSubscriberGuid()
            ->shouldBe('456');
    }

    public function it_should_get_a_list_of_requests()
    {
        $this->db->request(Argument::that(function ($prepared) {
            $query = $prepared->build();
            return $query['values'][0]->value() == '123';
        }))
            ->willReturn(new Rows([
                [
                    'publisher_guid' => new Bigint(123),
                    'subscriber_guid' => new Bigint(456),
                    'timestamp' => new Timestamp(time()),
                    'declined' => null,
                ],
                [
                    'publisher_guid' => new Bigint('1789'),
                    'subscriber_guid' => new Bigint('1123'),
                    'timestamp' => new Timestamp(time()),
                    'declined' => true,
                ]
                ], 'next-page-token'));

        $response = $this->getList([
            'publisher_guid' => '123',
            'show_declined' => true,
        ]);
        $response[0]->getPublisherGuid()
            ->shouldBe('123');
        $response[0]->getSubscriberGuid()
            ->shouldBe('456');
        $response[1]->getPublisherGuid()
            ->shouldBe('1789');
        $response[1]->getSubscriberGuid()
            ->shouldBe('1123');
        $response[1]->isDeclined()
            ->shouldBe(true);
    }

    public function it_should_add_to_repository()
    {
        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest->setPublisherGuid('123')
            ->setSubscriberGuid('456')
            ->setTimestampMs(1568711904123);

        $this->db->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            return $values[0]->value() === '123'
                && $values[1]->value() === '456'
                && $values[2]->time() === 1568711904123;
        }))
            ->willReturn(true);
        
        $this->add($subscriptionRequest)
            ->shouldReturn(true);
    }

    public function it_should_update_a_request()
    {
        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest->setPublisherGuid('123')
            ->setSubscriberGuid('456')
            ->setDeclined(true);

        $this->db->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            return $values[0] == true
                && $values[1]->value() === '123'
                && $values[2]->value() === '456';
        }))
            ->willReturn(true);
        
        $this->update($subscriptionRequest)
            ->shouldReturn(true);
    }

    public function it_should_delete_a_request()
    {
        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest->setPublisherGuid('123')
            ->setSubscriberGuid('456');

        $this->db->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            return $values[0]->value() === '123'
                && $values[1]->value() === '456';
        }))
            ->willReturn(true);
        
        $this->delete($subscriptionRequest)
            ->shouldReturn(true);
    }
}
