<?php

namespace Spec\Minds\Core\Media\Video\Transcoder;

use Google\Service\Transcoder;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Core\Media\Video\Transcoder\Repository;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles;
use Minds\Entities\Video;
use Minds\Common\Repository\Response;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Media\Video\CloudflareStreams;
use Minds\Core\Security\ACL;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TranscodeStatesSpec extends ObjectBehavior
{
    private $repository;
    private $save;
    private $cloudflareStreamsManager;

    public function let(Repository $repository, Save $save, CloudflareStreams\Manager $cloudflareStreamsManager)
    {
        $this->beConstructedWith($repository, $save, $cloudflareStreamsManager);
        $this->repository = $repository;
        $this->save = $save;
        $this->cloudflareStreamsManager = $cloudflareStreamsManager;
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

    public function it_should_not_call_cloudflare_if_video_was_already_transcoded()
    {
        $video = new Video();
        $video->set('guid', '123');
        $video->setTranscoder('cloudflare');
        $video->setTranscodingStatus(TranscodeStates::COMPLETED);

        $this->cloudflareStreamsManager->getVideoTranscodeStatus(Argument::any())->shouldNotBeCalled();

        $this->getStatus($video)
            ->shouldReturn(TranscodeStates::COMPLETED);
    }

    public function it_should_call_cloudflare_and_save_status_if_video_was_not_already_transcoded()
    {
        $video = new Video();
        $video->set('guid', '123');
        $video->setTranscoder('cloudflare');
        $video->setTranscodingStatus(TranscodeStates::TRANSCODING);

        $this->cloudflareStreamsManager->getVideoTranscodeStatus(Argument::any())->shouldBeCalled()
            ->willReturn(
                (new CloudflareStreams\TranscodeStatus)
                    ->setState(TranscodeStates::TRANSCODING)
                    ->setPct(20)
            );

        $this->getStatus($video)
            ->shouldReturn(TranscodeStates::TRANSCODING);
    }

    public function it_should_use_cloudflare_if_video_transcoder_was_cloudflare()
    {
        $video = new Video();
        $video->set('guid', '123');
        $video->setTranscoder('cloudflare');

        $this->cloudflareStreamsManager->getVideoTranscodeStatus(Argument::any())->shouldBeCalled()
            ->willReturn(
                (new CloudflareStreams\TranscodeStatus)
                    ->setState(TranscodeStates::TRANSCODING)
                    ->setPct(20)
            );
        
        $this->getStatus($video)
            ->shouldReturn(TranscodeStates::TRANSCODING);
    }

    public function it_should_use_minds_transcoder_if_video_transcoder_was_minds()
    {
        $video = new Video();
        $video->set('guid', '123');

        $this->repository->getList(Argument::any())->shouldBeCalled();

        $this->shouldNotThrow()->during('getStatus', [$video]);
    }
}
