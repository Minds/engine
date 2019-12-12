<?php

namespace Spec\Minds\Core\Media\Video\Transcoder\TranscodeExecutors;

use Minds\Core\Media\Video\Transcoder\TranscodeExecutors\FFMpegExecutor;
use Minds\Core\Media\Video\Transcoder\TranscodeStorage\TranscodeStorageInterface;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeExecutors\FailedTranscodeException;
use FFMpeg\FFMpeg as FFMpegClient;
use FFMpeg\FFProbe as FFProbeClient;
use FFMpeg\Filters\Video\ResizeFilter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FFMpegExecutorSpec extends ObjectBehavior
{
    private $ffmpeg;
    private $ffprobe;
    private $transcodeStorage;

    public function let(FFMpegClient $ffmpeg, FFProbeClient $ffprobe, TranscodeStorageInterface $transcodeStorage)
    {
        $this->beConstructedWith(null, $ffmpeg, $ffprobe, $transcodeStorage);
        $this->ffmpeg = $ffmpeg;
        $this->ffprobe = $ffprobe;
        $this->transcodeStorage = $transcodeStorage;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FFMpegExecutor::class);
    }

    public function it_should_transcode_thumbnails(
        Transcode $transcode,
        \FFMpeg\Media\Video $ffmpegVideo,
        \FFMpeg\FFProbe\DataMapping\Format $ffprobeFormat,
        \FFMpeg\Media\Frame $ffmpegFrame
    ) {
        $transcode->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $transcode->getProfile()
            ->shouldBeCalled()
            ->willReturn(new TranscodeProfiles\Thumbnails());
       
        $this->transcodeStorage->downloadToTmp(Argument::type(Transcode::class))
            ->willReturn('/tmp/fake-path-for-source');

        $this->ffmpeg->open('/tmp/fake-path-for-source')
            ->shouldBeCalled()
            ->willReturn($ffmpegVideo);

        $this->ffprobe->streams('/tmp/fake-path-for-source')
            ->shouldBeCalled()
            ->willReturn($this->ffprobe);
        
        $this->ffprobe->format('/tmp/fake-path-for-source')
            ->shouldBeCalled()
            ->willReturn($ffprobeFormat);
        
        $ffprobeFormat->get('duration')
            ->willReturn(120);

        $ffmpegVideo->frame(Argument::any())
            ->shouldBeCalled()
            ->willReturn($ffmpegFrame);
        
        $ffmpegFrame->save(Argument::any())
            ->shouldBeCalled();

        // These are all the thumbnails thast should have been called
        $this->transcodeStorage->add($transcode, '/tmp/fake-path-for-source-thumbnails/thumbnail-00000.png')
            ->shouldBeCalled();
        $this->transcodeStorage->add($transcode, '/tmp/fake-path-for-source-thumbnails/thumbnail-00001.png')
            ->shouldBeCalled();
        $this->transcodeStorage->add($transcode, '/tmp/fake-path-for-source-thumbnails/thumbnail-00060.png')
            ->shouldBeCalled();
        $this->transcodeStorage->add($transcode, '/tmp/fake-path-for-source-thumbnails/thumbnail-00119.png')
            ->shouldBeCalled();
        // $this->transcodeStorage->add($transcode, '/tmp/fake-path-for-source-thumbnails/thumbnail-00120.png')
        //     ->shouldBeCalled();

        $transcode->setProgress(100)
            ->shouldBeCalled();

        $transcode->setStatus('completed')
            ->shouldBeCalled();

        $wrapped = $transcode->getWrappedObject();
        $this->getWrappedObject()->transcode($wrapped, function ($progress) {
        });
    }

    public function it_should_transcode_video(
        Transcode $transcode,
        \FFMpeg\Media\Video $ffmpegVideo,
        \FFMpeg\FFProbe\DataMapping\Format $ffprobeFormat,
        \FFMpeg\Filters\Video\VideoFilters $ffmpegVideoFilters
    ) {
        $transcode->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $transcode->getProfile()
            ->shouldBeCalled()
            ->willReturn(new TranscodeProfiles\X264_360p());
       
        $this->transcodeStorage->downloadToTmp(Argument::type(Transcode::class))
            ->willReturn('/tmp/fake-path-for-source');

        $this->ffmpeg->open('/tmp/fake-path-for-source')
            ->shouldBeCalled()
            ->willReturn($ffmpegVideo);
        
        $this->ffprobe->streams('/tmp/fake-path-for-source')
            ->shouldBeCalled()
            ->willReturn($this->ffprobe);

        $ffmpegVideo->filters()
            ->shouldBeCalled()
            ->willReturn($ffmpegVideoFilters);

        $ffmpegVideoFilters->resize(Argument::any(), ResizeFilter::RESIZEMODE_SCALE_WIDTH)
            ->shouldBeCalled()
            ->willReturn($ffmpegVideoFilters);

        $ffmpegVideoFilters->synchronize()
            ->shouldBeCalled();

        $ffmpegVideo->save(Argument::that(function ($format) {
            return $format->getKiloBitRate() === 500
                && $format->getAudioKiloBitrate() === 80;
        }), '/tmp/fake-path-for-source-360.mp4')
            ->shouldBeCalled();

        $this->transcodeStorage->add($transcode, '/tmp/fake-path-for-source-360.mp4')
            ->shouldBeCalled();

        $transcode->setStatus('completed')
            ->shouldBeCalled();

        $wrapped = $transcode->getWrappedObject();
        $this->getWrappedObject()->transcode($wrapped, function ($progress) {
        });
    }

    public function it_should_transcode_video_but_register_failure(
        Transcode $transcode,
        \FFMpeg\Media\Video $ffmpegVideo,
        \FFMpeg\FFProbe\DataMapping\Format $ffprobeFormat,
        \FFMpeg\Filters\Video\VideoFilters $ffmpegVideoFilters
    ) {
        $transcode->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $transcode->getProfile()
            ->shouldBeCalled()
            ->willReturn(new TranscodeProfiles\X264_360p());
       
        $this->transcodeStorage->downloadToTmp(Argument::type(Transcode::class))
            ->willReturn('/tmp/fake-path-for-source');

        $this->ffmpeg->open('/tmp/fake-path-for-source')
            ->shouldBeCalled()
            ->willReturn($ffmpegVideo);
        
        $this->ffprobe->streams('/tmp/fake-path-for-source')
            ->shouldBeCalled()
            ->willReturn($this->ffprobe);

        $ffmpegVideo->filters()
            ->shouldBeCalled()
            ->willReturn($ffmpegVideoFilters);

        $ffmpegVideoFilters->resize(Argument::any(), ResizeFilter::RESIZEMODE_SCALE_WIDTH)
            ->shouldBeCalled()
            ->willReturn($ffmpegVideoFilters);

        $ffmpegVideoFilters->synchronize()
            ->shouldBeCalled();

        $ffmpegVideo->save(Argument::that(function ($format) {
            return $format->getKiloBitRate() === 500
                && $format->getAudioKiloBitrate() === 80;
        }), '/tmp/fake-path-for-source-360.mp4')
            ->shouldBeCalled()
            ->willThrow(new \FFMpeg\Exception\RuntimeException());

        $this->transcodeStorage->add($transcode, '/tmp/fake-path-for-source-360.mp4')
            ->shouldNotBeCalled();

        $transcode->setStatus('failed')
            ->shouldBeCalled();

        $wrapped = $transcode->getWrappedObject();
        try {
            $this->getWrappedObject()->transcode($wrapped, function ($progress) {
            });
            throw new \Exception("An exception should have been thrown doe failed transcode");
        } catch (FailedTranscodeException $e) {
            // We throw a new exception above if the one we are expecting isn't called
        }
    }
}
