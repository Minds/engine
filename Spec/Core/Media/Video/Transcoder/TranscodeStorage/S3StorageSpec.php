<?php

namespace Spec\Minds\Core\Media\Video\Transcoder\TranscodeStorage;

use Minds\Core\Media\Video\Transcoder\TranscodeStorage\S3Storage;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Media\Video\Transcoder\TranscodeProfiles;
use Psr\Http\Message\RequestInterface;
use Aws\S3\S3Client;
use Minds\Core\Config\Config;
use Oracle\Oci\Common\OciResponse;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class S3StorageSpec extends ObjectBehavior
{
    private $configMock;
    private $awsS3Mock;
    private $ociS3Mock;
    private $osClientMock;

    public function let(Config $configMock, S3Client $awsS3Mock, S3Client $ociS3Mock, ObjectStorageClient $osClientMock)
    {
        $this->beConstructedWith($configMock, $awsS3Mock, $ociS3Mock, $osClientMock);
        $this->configMock = $configMock;
        $this->awsS3Mock = $awsS3Mock;
        $this->ociS3Mock = $ociS3Mock;
        $this->osClientMock = $osClientMock;
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
    
        $this->awsS3Mock->putObject(Argument::that(function ($args) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($transcode, tempnam(sys_get_temp_dir(), 'my-fake-path'));
    }

    public function it_should_return_a_signed_url_for_client_side_uploads_from_oci(
        Transcode $transcode,
        OciResponse $ociResponse,
    ) {
        $this->configMock->get('transcoder')->willReturn([
            'oci_primary' => true,
        ]);
        $this->configMock->get('oci')->willReturn([
            'api_auth' => [
                'bucket_namespace' => 'phpspec',
            ],
        ]);

        $transcode->getGuid()
            ->willReturn(123);
        $transcode->getProfile()
            ->willReturn(new TranscodeProfiles\Source());

        $this->osClientMock->createPreauthenticatedRequest(Argument::any())
            ->shouldBeCalled()
            ->willReturn($ociResponse);

        $ociResponse->getJson()->willReturn((object) [ 'fullPath' => 'oci-signed-url' ]);

        $this->getClientSideUploadUrl($transcode)
            ->shouldReturn('oci-signed-url');
    }

    public function it_should_return_a_signed_url_for_client_side_uploads(
        Transcode $transcode,
        \Aws\CommandInterface $cmd,
        RequestInterface $request
    ) {
        $this->configMock->get('transcoder')->willReturn([
            'oci_primary' => false,
        ]);

        $transcode->getGuid()
            ->willReturn(123);
        $transcode->getProfile()
            ->willReturn(new TranscodeProfiles\Source());

        $this->awsS3Mock->getCommand('PutObject', [
            'Bucket' => 'cinemr',
            'Key' => "/123/source",
        ])
            ->shouldBeCalled()
            ->willReturn($cmd);
        
        $this->awsS3Mock->createPresignedRequest(Argument::any(), Argument::any())
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
