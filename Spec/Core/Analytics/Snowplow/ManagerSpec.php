<?php

namespace Spec\Minds\Core\Analytics\Snowplow;

use Minds\Core\Analytics\Snowplow\Manager;
use Minds\Core\Analytics\Snowplow\Events;
use Snowplow\Tracker\Emitters\CurlEmitter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var CurlEmitter */
    protected $emitter;

    public function let(CurlEmitter $emitter)
    {
        $this->beConstructedWith($emitter);
        $this->emitter = $emitter;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_emit_event(Events\SnowplowEventInterface $event)
    {
        $event->getSchema()
            ->willReturn('iglu:my-schema-uri');

        $event->getData()
            ->willReturn([
                'foo' => 'bar',
            ]);
        
        $event->getContext()
            ->willReturn([]);

        $this->setSubject()->emit($event);
    }
}
