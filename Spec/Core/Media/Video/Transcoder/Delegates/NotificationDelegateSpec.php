<?php

namespace Spec\Minds\Core\Media\Video\Transcoder\Delegates;

use Minds\Core\Media\Video\Transcoder\Delegates\NotificationDelegate;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Video;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotificationDelegateSpec extends ObjectBehavior
{
    private $transcodeStates;
    private $eventsDispatcher;
    private $entitiesBuilder;

    public function let(TranscodeStates $transcodeStates, EventsDispatcher $eventsDispatcher, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($transcodeStates, $eventsDispatcher, $entitiesBuilder);
        $this->transcodeStates = $transcodeStates;
        $this->eventsDispatcher = $eventsDispatcher;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    private function mockFetchVideo()
    {
        $this->entitiesBuilder->single('123')
            ->shouldBeCalled()
            ->willReturn(
                (new Video)
                ->set('guid', '123')
                ->set('owner_guid', '456')
            );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NotificationDelegate::class);
    }

    // public function it_should_send_notification_of_completed()
    // {
    //     $transcode = new Transcode();
    //     $transcode->setGuid('123');

    //     $this->mockFetchVideo();

    //     $this->transcodeStates->getStatus(Argument::that(function ($video) {
    //         return $video->getGuid() === '123';
    //     }))
    //         ->shouldBeCalled()
    //         ->willReturn('completed');

    //     $this->eventsDispatcher->trigger('notification', 'transcoder', Argument::type('array'))
    //         ->shouldBeCalled();

    //     $this->onTranscodeCompleted($transcode);
    // }

    // public function it_should_send_notification_of_failed()
    // {
    //     $transcode = new Transcode();
    //     $transcode->setGuid('123');

    //     $this->mockFetchVideo();

    //     $this->transcodeStates->getStatus(Argument::that(function ($video) {
    //         return $video->getGuid() === '123';
    //     }))
    //         ->shouldBeCalled()
    //         ->willReturn('failed');

    //     $this->eventsDispatcher->trigger('notification', 'transcoder', Argument::type('array'))
    //         ->shouldBeCalled();

    //     $this->onTranscodeCompleted($transcode);
    // }

    public function it_should_do_nothing()
    {
        $transcode = new Transcode();
        $transcode->setGuid('123');

        $this->mockFetchVideo();

        $this->transcodeStates->getStatus(Argument::that(function ($video) {
            return $video->getGuid() === '123';
        }))
            ->shouldBeCalled()
            ->willReturn('transcoding');

        $this->eventsDispatcher->trigger('notification', 'transcoder', Argument::type('array'))
            ->shouldNotBeCalled();

        $this->onTranscodeCompleted($transcode);
    }
}
