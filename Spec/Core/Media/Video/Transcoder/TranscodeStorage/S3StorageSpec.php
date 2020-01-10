<?php

namespace Spec\Minds\Core\Media\Video\Transcoder\TranscodeStorage;

use Minds\Core\Media\Video\Transcoder\TranscodeStorage\S3Storage;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles;
use Psr\Http\Message\RequestInterface;
use Aws\S3\S3Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class S3StorageSpec extends ObjectBehavior
{
    private $s3;

    public function let(S3Client $s3)
    {
        $this->beConstructedWith(null, $s3);
        $this->s3 = $s3;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(S3Storage::class);
    }

    public function it_should_upload_file(Transcode $transcode)
    {
        $transcode->getGuid()
            ->willReturn(123);
        $transcode->getProfile()
            ->willReturn(new TranscodeProfiles\X264_360p());
    
        $this->s3->putObject(Argument::that(function ($args) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($transcode, tempnam(sys_get_temp_dir(), 'my-fake-path'));
    }

    public function it_should_return_a_signed_url_for_client_side_uploads(
        Transcode $transcode,
        \Aws\CommandInterface $cmd,
        RequestInterface $request
    ) {
        $transcode->getGuid()
            ->willReturn(123);
        $transcode->getProfile()
            ->willReturn(new TranscodeProfiles\Source());

        $this->s3->getCommand('PutObject', [
            'Bucket' => 'cinemr',
            'Key' => "/123/source",
        ])
            ->shouldBeCalled()
            ->willReturn($cmd);
        
        $this->s3->createPresignedRequest(Argument::any(), Argument::any())
            ->willReturn($request);
            
        $request->getUri()
            ->willReturn('aws-signed-url');

        $this->getClientSideUploadUrl($transcode)
            ->shouldReturn('aws-signed-url');
    }

    public function it_should_download_file(Transcode $transcode)
    {
        $transcode->getGuid()
            ->willReturn(123);
        $transcode->getProfile()
            ->willReturn(new TranscodeProfiles\Source());
    
        $this->downloadToTmp($transcode)
            ->shouldContain("123-source");
    }
}
