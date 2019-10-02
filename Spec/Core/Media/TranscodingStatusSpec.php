<?php

namespace Spec\Minds\Core\Media;

use Minds\Core\Media\TranscodingStatus;
use PhpSpec\ObjectBehavior;
use Aws\Result;
use Minds\Entities\Video;
use Minds\Core\Config;

class TranscodingStatusSpec extends ObjectBehavior
{
    /** @var Video $video */
    private $video;
    /** @var Config $config */
    private $config;

    public function let(Config $config)
    {
        $this->video = new Video();
        $this->video->guid = 123;
        $this->config = $config;
        $this->config->get('transcoder')->willReturn([
            'dir' => 'test',
            'presets' => [
                [
                    'height' => 1080,
                    'formats' => ['mp4', 'webm']
                ],
                [
                    'height' => 360,
                    'formats' => ['mp4', 'webm']
                ]
            ]
        ]);
    }

    public function it_is_initializable()
    {
        $this->beConstructedWith($this->video, new Result(), $this->config);
        $this->shouldHaveType(TranscodingStatus::class);
    }

    public function it_should_parse_empty_data()
    {
        $this->beConstructedWith($this->video, new Result(), $this->config);
        $this->hasSource()->shouldReturn(false);
        $this->getTranscodes()->shouldReturn([]);
        $this->getThumbnails()->shouldReturn([]);
        $this->isTranscodingComplete()->shouldReturn(false);
        $this->getExpectedTranscodeCount()->shouldReturn(2);
    }

    public function it_should_parse_data()
    {
        $result = new Result(['Contents' => [
            ['Key' => '/test/123/1080.mp4'],
            ['Key' => '/test/123/1080.webm'],
            ['Key' => '/test/123/360.mp4'],
            ['Key' => '/test/123/360.webm'],
            ['Key' => '/test/123/thumbnail-00000.png']
        ]]);
        $this->beConstructedWith($this->video, $result, $this->config);
        $this->hasSource()->shouldReturn(false);
        $this->getTranscodes()->shouldReturn(['/test/123/1080.mp4', '/test/123/1080.webm', '/test/123/360.mp4', '/test/123/360.webm']);
        $this->getThumbnails()->shouldContain('/test/123/thumbnail-00000.png');
        $this->isTranscodingComplete()->shouldReturn(true);
    }

    public function it_should_say_still_transcoding()
    {
        $result = new Result(['Contents' => [
            ['Key' => '/test/123/360.mp4'],
            ['Key' => '/test/123/thumbnail-00000.png']
        ]]);
        $this->beConstructedWith($this->video, $result, $this->config);
        $this->hasSource()->shouldReturn(false);
        $this->getTranscodes()->shouldReturn(['/test/123/360.mp4']);
        $this->getThumbnails()->shouldContain('/test/123/thumbnail-00000.png');
        $this->isTranscodingComplete()->shouldReturn(false);
    }
}
