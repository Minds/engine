<?php

namespace Spec\Minds\Core\Analytics\Metrics;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Analytics\Snowplow;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Index;

class EventSpec extends ObjectBehavior
{
    /** @var Client */
    protected $es;

    /** @var Snowplow\Manager */
    protected $snowplowManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function let(Client $es, EntitiesBuilder $entitiesBuilder, Snowplow\Manager $snowplowManager)
    {
        $this->beConstructedWith($es, $snowplowManager, $entitiesBuilder);
        $this->es = $es;
        $this->snowplowManager = $snowplowManager;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Analytics\Metrics\Event');
    }

    public function it_should_set_a_variable()
    {
        $this->setType('hello')->shouldReturn($this);
        $this->getData()->shouldReturn([
            'type' => 'hello'
        ]);
    }

    public function it_should_set_camel_case()
    {
        $this->setOwnerGuid('hello')->shouldReturn($this);
        $this->setNotcamelcase('boo')->shouldReturn($this);
        $this->setSnake_Case('woo')->shouldReturn($this);
        $this->getData()->shouldReturn([
            'owner_guid' => 'hello',
            'notcamelcase' => 'boo',
            'snake__case' => 'woo'
        ]);
    }

    public function it_should_push(Index $prepared)
    {

        /*$prepared->query([
            'body' => $this->getData(),
            'index' => "minds-metrics-" . date('m-Y', time()),
            'type' => 'action',
            'client' => [
                'timeout' => 2,
                'connect_timeout' => 1
            ]
        ])->shouldBeCalled();
        $prepared->build()->shouldBeCalled();*/

        $this->es->request(Argument::type('Minds\Core\Data\ElasticSearch\Prepared\Index'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setType('action');
        $this->push()->shouldBe(true);
        $this->getData()->shouldHaveKey('@timestamp');
    }

    public function it_should_post_action_to_snowplow()
    {
        $this->snowplowManager->setSubject(Argument::that(function ($user) {
            return true;
        }))
            ->willReturn($this->snowplowManager);
    
        $this->snowplowManager->emit(Argument::that(function ($event) {
            return true;
        }))
            ->shouldBeCalled();

        $this->es->request(Argument::type('Minds\Core\Data\ElasticSearch\Prepared\Index'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setType('action');
        $this->setAction('vote:up');
        $this->setUserGuid('123');

        $this->push()->shouldBe(true);
    }
}
