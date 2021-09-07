<?php

namespace Spec\Minds\Core\Notifications\Push\DeviceSubscriptions;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscriptionListOpts;
use Minds\Core\Notifications\Push\DeviceSubscriptions\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    protected $cql;

    public function let(Client $cql)
    {
        $this->beConstructedWith($cql);
        $this->cql = $cql;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_list()
    {
        $opts = new DeviceSubscriptionListOpts();
        $opts->setUserGuid('123');

        $this->cql->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->willReturn([
                [
                    'user_guid' => '123',
                    'device_token' => 'token-1',
                    'service' => 'apns',
                ]
            ]);

        $response = $this->getList($opts);
        $response->shouldHaveCount(1);
    }

    public function it_should_add(DeviceSubscription $deviceSubscription)
    {
        $this->cql->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->willReturn(true);
    
        $this->add($deviceSubscription)
            ->shouldReturn(true);
    }

    public function it_should_delete(DeviceSubscription $deviceSubscription)
    {
        $this->cql->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->willReturn(true);
    
        $this->delete($deviceSubscription)
            ->shouldReturn(true);
    }
}
