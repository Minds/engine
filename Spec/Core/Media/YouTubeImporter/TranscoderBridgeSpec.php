<?php

namespace Spec\Minds\Core\Media\YouTubeImporter;

use Minds\Core\Media\YouTubeImporter\TranscoderBridge;
use Minds\Core\Media\YouTubeImporter\YTVideoSource;
use Minds\Core\Media\Video\Transcoder\Manager as TranscoderManager;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles;
use Minds\Core\Media\Video\Transcoder\TranscodeStorage\TranscodeStorageInterface;
use Minds\Entities\Video;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TranscoderBridgeSpec extends ObjectBehavior
{
    /** @var TranscoderManager */
    protected $transcoderManager;

    /** @var TranscodeStorageInterface */
    protected $transcodeStorage;

    public function let(TranscoderManager $transcoderManager, TranscodeStorageInterface $transcodeStorage)
    {
        $this->transcoderManager = $transcoderManager;
        $this->transcodeStorage = $transcodeStorage;
        $this->beConstructedWith($transcoderManager, $transcodeStorage);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TranscoderBridge::class);
    }

    public function it_should_add_video_from_youtube_source(YTVideoSource $source)
    {
        $tmpFile = tmpfile();
        $tmpPath = stream_get_meta_data($tmpFile)['uri'];

        $source->getItag()
            ->willReturn(18);

        $source->getUrl()
            ->willReturn($tmpPath);

        $this->transcoderManager->add(Argument::that(function ($transcode) {
            return true;
        }), false);

        $this->transcoderManager->add(Argument::that(function ($transcode) {
            return $transcode->getProfile()->getId() === 'X264_360p';
        }), true);

        $this->transcodeStorage->add(Argument::that(function ($transcode) {
            return true;
        }), Argument::type('string'))
            ->willReturn(true);

        $this->transcoderManager->update(Argument::that(function ($transcode) {
            return true;
        }), [ 'status', 'progress' ]);

        $video = new Video();
        $this->addFromYouTube($video, $source)
            ->shouldBe(true);
    }

    public function it_should_skip_if_invalid_itag(YTVideoSource $source)
    {
        $source->getItag()
            ->willReturn(1);

        $video = new Video();
        $this->addFromYouTube($video, $source)
            ->shouldBe(false);
    }
}
