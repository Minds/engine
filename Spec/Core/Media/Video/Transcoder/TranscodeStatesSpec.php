<?php

namespace Spec\Minds\Core\Media\Video\Transcoder;

use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Core\Media\Video\Transcoder\Repository;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles;
use Minds\Entities\Video;
use Minds\Common\Repository\Response;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TranscodeStatesSpec extends ObjectBehavior
{
    private $repository;

    public function let(Repository $repository)
    {
        $this->beConstructedWith($repository);
        $this->repository = $repository;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TranscodeStates::class);
    }

    public function it_should_return_transcoding_status()
    {
        $video = new Video();
        $video->set('guid', '123');

        $this->repository->getList([ 'guid' => '123' ])
            ->shouldBeCalled()
            ->willReturn(new Response([
                (new Transcode())
                    ->setProfile(new TranscodeProfiles\X264_360p())
                    ->setStatus('transcoding')
                    ->setLastEventTimestampMs(round(microtime(true) * 1000)),
                (new Transcode())
                    ->setProfile(new TranscodeProfiles\X264_720p())
                    ->setStatus('completed')
                    ->setLastEventTimestampMs(round(microtime(true) * 1000))
            ]));

        $this->getStatus($video)
            ->shouldReturn('transcoding');
    }

    public function it_should_declare_a_timeout()
    {
        $video = new Video();
        $video->set('guid', '123');

        $this->repository->getList([ 'guid' => '123' ])
            ->shouldBeCalled()
            ->willReturn(new Response([
                (new Transcode())
                    ->setProfile(new TranscodeProfiles\X264_360p())
                    ->setStatus('transcoding')
                    ->setLastEventTimestampMs(round(microtime(true) * 1000) - 700000),
                (new Transcode())
                    ->setProfile(new TranscodeProfiles\X264_720p())
                    ->setStatus('transcoding')
                    ->setLastEventTimestampMs(round(microtime(true) * 1000) - 700000)
            ]));

        $this->getStatus($video)
            ->shouldReturn('failed');
    }

    public function it_should_return_completed_status()
    {
        $video = new Video();
        $video->set('guid', '123');

        $this->repository->getList([ 'guid' => '123' ])
            ->shouldBeCalled()
            ->willReturn(new Response([
                (new Transcode())
                    ->setProfile(new TranscodeProfiles\X264_360p())
                    ->setStatus('completed')
                    ->setLastEventTimestampMs(round(microtime(true) * 1000)),
                (new Transcode())
                    ->setProfile(new TranscodeProfiles\X264_720p())
                    ->setStatus('completed')
                    ->setLastEventTimestampMs(round(microtime(true) * 1000))
            ]));

        $this->getStatus($video)
            ->shouldReturn('completed');
    }

    public function it_should_return_failed_status()
    {
        $video = new Video();
        $video->set('guid', '123');

        $this->repository->getList([ 'guid' => '123' ])
            ->shouldBeCalled()
            ->willReturn(new Response([
                (new Transcode())
                    ->setProfile(new TranscodeProfiles\X264_360p())
                    ->setStatus('failed')
                    ->setLastEventTimestampMs(round(microtime(true) * 1000)),
                (new Transcode())
                    ->setProfile(new TranscodeProfiles\X264_720p())
                    ->setStatus('failed')
                    ->setLastEventTimestampMs(round(microtime(true) * 1000))
            ]));

        $this->getStatus($video)
            ->shouldReturn('failed');
    }
}
