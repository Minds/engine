<?php

namespace Spec\Minds\Core\Media\Services;

use Aws\CommandInterface;
use Aws\S3\S3Client;
use FFMpeg\FFMpeg as FFMpegClient;
use FFMpeg\FFProbe as FFProbeClient;
use Minds\Core\Media\Services\FFMpeg;
use Minds\Core\Queue\Interfaces\QueueClient;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;

class FFMpegSpec extends ObjectBehavior
{
    private $s3;
    private $ffmpeg;
    private $ffprobe;
    private $queue;

    public function let(
        QueueClient $queueClient,
        FFMpegClient $ffmpeg,
        FFProbeClient $ffprobe,
        S3Client $s3,
    ): void {
        $this->ffmpeg = $ffmpeg;
        $this->ffprobe = $ffprobe;
        $this->queue = $queueClient;
        $this->s3 = $s3;

        $this->beConstructedWith(
            $this->queue,
            $this->ffmpeg,
            $this->ffprobe,
            $this->s3,
            null
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FFMpeg::class);
    }

    public function it_should_get_a_presigned_urn(
        CommandInterface $cmd,
        RequestInterface $request
    ) {
        $this->s3->getCommand('PutObject', [
            'Bucket' => 'cinemr',
            'Key' => "/123/source",
        ])
            ->shouldBeCalled()
            ->willReturn($cmd);

        $request->getUri()
            ->willReturn('aws-signed-url');

        $this->s3->createPresignedRequest($cmd, Argument::any())
            ->willReturn($request);


        $this->setKey(123);

        $this->getPresignedUrl()
            ->shouldReturn('aws-signed-url');
    }
}
