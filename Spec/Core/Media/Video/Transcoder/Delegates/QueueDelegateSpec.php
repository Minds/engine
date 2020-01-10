<?php

namespace Spec\Minds\Core\Media\Video\Transcoder\Delegates;

use Minds\Core\Media\Video\Transcoder\Delegates\QueueDelegate;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Core\Media\Video\Transcoder\Transcode;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class QueueDelegateSpec extends ObjectBehavior
{
    private $queueClient;

    public function let(QueueClient $queueClient)
    {
        $this->beConstructedWith($queueClient);
        $this->queueClient = $queueClient;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(QueueDelegate::class);
    }

    public function it_should_add_to_queue()
    {
        $transcode = new Transcode();

        $this->queueClient->setQueue('Transcode')
            ->shouldBeCalled()
            ->willReturn($this->queueClient);
        
        $this->queueClient->send(Argument::that(function ($message) use ($transcode) {
            return unserialize($message['transcode']) == $transcode;
        }))
            ->shouldBeCalled();

        $this->onAdd($transcode);
    }
}
