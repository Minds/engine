<?php

namespace Spec\Minds\Core\Media\Video;

use Minds\Core\Config;
use Aws\S3\S3Client;
use Minds\Core\Media\Video\Manager;
use Minds\Entities\Video;
use Psr\Http\Message\RequestInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Media\Services\FFMpeg;
use Minds\Entities\Entity;
use Minds\Entities\User;

class ManagerSpec extends ObjectBehavior
{
    private $config;
    private $s3;
    private $transcoder;

    public function let(Config $config, S3Client $s3, FFMpeg $transcoder)
    {
        $this->beConstructedWith($config, $s3, $transcoder);
        $this->config = $config;
        $this->s3 = $s3;
        $this->transcoder = $transcoder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_a_signed_720p_video_url(RequestInterface $request, \Aws\CommandInterface $cmd)
    {
        $this->config->get('transcoder')
            ->willReturn([
                'dir' => 'dir',
            ]);
        $this->config->get('aws')
            ->willReturn([
                'region' => 'us-east-1',
                'useRoles' => true,
            ]);

        $this->config->get('cinemr_url')
            ->willReturn('https://url.com/cinemr');

        $this->s3->getCommand('GetObject', [
            'Bucket' => 'cinemr',
            'Key' => 'dir/123/720.mp4'
        ])
            ->shouldBeCalled()
            ->willReturn($cmd);

        $request->getUri()
            ->willReturn('s3-signed-url-here');

        $this->s3->createPresignedRequest(Argument::any(), Argument::any())
            ->willReturn($request);

        $video = new Video();
        $video->set('cinemr_guid', 123);
        $video->set('access_id', ACCESS_PRIVATE);
        $this->getPublicAssetUri($video, '720.mp4')
            ->shouldBe('s3-signed-url-here');
    }

    public function it_should_get_an_unsigned_720p_video_url(RequestInterface $request, \Aws\CommandInterface $cmd)
    {
        $this->config->get('transcoder')
            ->willReturn([
                'dir' => 'dir',
            ]);
        $this->config->get('aws')
            ->willReturn([
                'region' => 'us-east-1',
                'useRoles' => true,
            ]);

        $this->config->get('cinemr_url')
            ->willReturn('https://url.com/cinemr');

        $this->s3->getCommand('GetObject', [
            'Bucket' => 'cinemr',
            'Key' => 'dir/123/720.mp4'
        ])
            ->shouldBeCalled()
            ->willReturn($cmd);

        $request->getUri()
            ->willReturn('s3-signed-url-here');

        $this->s3->createPresignedRequest(Argument::any(), Argument::any())
            ->willReturn($request);

        $video = new Video();
        $video->set('cinemr_guid', 123);
        $this->getPublicAssetUri($video, '720.mp4')
            ->shouldBe('https://url.com/cinemr123/720.mp4');
    }

    public function it_should_manually_transcode_a_non_hd_video(FFMpeg $transcoder, Entity $entity, User $owner)
    {
        $guid = '123';
        $hd = false;

        $owner->isPro()->shouldBeCalled()->willReturn($hd);
        $entity->getOwnerEntity()->shouldBeCalled()->willReturn($owner);
        $entity->get('guid')->shouldBeCalled()->willReturn($guid);

        $transcoder->setKey($guid)->shouldBeCalled();
        $transcoder->setFullHD($hd)->shouldBeCalled();
        $transcoder->transcode()->shouldBeCalled();

        $this->transcode($entity)
            ->shouldReturn(true);
    }

    public function it_should_manually_transcode_a_hd_video(FFMpeg $transcoder, Entity $entity, User $owner)
    {
        $guid = '123';
        $hd = true;

        $owner->isPro()->shouldBeCalled()->willReturn($hd);
        $entity->getOwnerEntity()->shouldBeCalled()->willReturn($owner);
        $entity->get('guid')->shouldBeCalled()->willReturn($guid);

        $transcoder->setKey($guid)->shouldBeCalled();
        $transcoder->setFullHD($hd)->shouldBeCalled();
        $transcoder->transcode()->shouldBeCalled();

        $this->transcode($entity)
            ->shouldReturn(true);
    }
}
