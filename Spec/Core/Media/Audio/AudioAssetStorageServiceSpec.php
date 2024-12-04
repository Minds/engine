<?php

namespace Spec\Minds\Core\Media\Audio;

use Minds\Core\Config\Config;
use Minds\Core\Media\Audio\AudioAssetStorageService;
use Minds\Core\Media\Audio\AudioEntity;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Aws\S3\S3Client;
use Minds\Common\Access;
use Minds\Core\Media\MediaDownloader\MediaDownloaderInterface;
use Oracle\Oci\Common\OciResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AudioAssetStorageServiceSpec extends ObjectBehavior
{
    /** @var Config */
    protected $configMock;

    /** @var S3Client */
    protected $s3ClientMock;

    /** @var ObjectStorageClient */
    protected $objectStorageClientMock;

    /** @var MediaDownloaderInterface */
    protected $audioDownloaderMock;

    public function let(
        Config $configMock,
        S3Client $s3ClientMock,
        ObjectStorageClient $objectStorageClientMock,
        MediaDownloaderInterface $audioDownloaderMock
    ) {
        $this->configMock = $configMock;
        $this->s3ClientMock = $s3ClientMock;
        $this->objectStorageClientMock = $objectStorageClientMock;
        $this->audioDownloaderMock = $audioDownloaderMock;

        $this->beConstructedWith(
            $configMock,
            $s3ClientMock,
            $objectStorageClientMock,
            $audioDownloaderMock
        );

        $configMock->get('storage')->willReturn([
            'oci_bucket_name' => 'test-bucket'
        ]);

        $configMock->get('oci')->willReturn([
            'api_auth' => [
                'bucket_namespace' => 'test-namespace'
            ]
        ]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AudioAssetStorageService::class);
    }

    public function it_should_download_to_tmpfile()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $this->s3ClientMock->getObject(Argument::that(function ($args) {
            return $args['Bucket'] === 'test-bucket'
                && $args['Key'] === '/data/audio/123/source'
                && is_string($args['SaveAs']);
        }))->willReturn(true);

        $this->downloadToTmpfile($audioEntity)->shouldBeResource();
    }

    public function it_should_download_to_tmpfile_from_remote_url(
        ResponseInterface $responseMock,
        StreamInterface $streamMock,
        AudioEntity $audioEntityMock
    ) {
        $audioEntityMock = new AudioEntity(
            guid: 234,
            ownerGuid: 456,
            accessId: Access::UNLISTED,
            durationSecs: 100,
            remoteFileUrl: 'https://example.minds.com/audio.mp3'
        );

        $this->audioDownloaderMock->download('https://example.minds.com/audio.mp3')
            ->shouldBeCalled()
            ->willReturn($responseMock);

        $responseMock->getBody()->willReturn($streamMock);

        $streamMockReadCallCount = 0;
        $streamMock->read(1024)->will(function () use (&$streamMockReadCallCount) {
            $streamMockReadCallCount++;
            return $streamMockReadCallCount === 1 ?
                    'test-content' :
                    null;
        });
        
        $this->s3ClientMock->getObject(Argument::any())
            ->shouldNotBeCalled();

        $this->downloadToTmpfile($audioEntityMock)->shouldBeResource();
    }

    public function it_should_download_to_memory()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $this->s3ClientMock->getObject(Argument::that(function ($args) {
            return $args['Bucket'] === 'test-bucket'
                && $args['Key'] === '/data/audio/123/source';
        }))->willReturn(['Body' => 'test-content']);

        $this->downloadToMemory($audioEntity)->shouldReturn('test-content');
    }

    public function it_should_get_download_url(OciResponse $responseMock)
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $responseMock->getJson()->willReturn((object)[
            'fullPath' => 'https://test-url'
        ]);

        $this->objectStorageClientMock->createPreauthenticatedRequest(Argument::that(function ($args) {
            return $args['namespaceName'] === 'test-namespace'
                && $args['bucketName'] === 'test-bucket'
                && $args['createPreauthenticatedRequestDetails']['name'] === '/data/audio/123/source'
                && $args['createPreauthenticatedRequestDetails']['objectName'] === '/data/audio/123/source'
                && $args['createPreauthenticatedRequestDetails']['accessType'] === 'ObjectRead'
                && is_string($args['createPreauthenticatedRequestDetails']['timeExpires']);
        }))->willReturn($responseMock);

        $this->getDownloadUrl($audioEntity)->shouldReturn('https://test-url');
    }

    public function it_should_upload_from_source()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $tmpfile = tmpfile();
        fwrite($tmpfile, 'test-content');
        $source = stream_get_meta_data($tmpfile)['uri'];

        $this->s3ClientMock->putObject(Argument::that(function ($args) {
            return $args['Bucket'] === 'test-bucket'
                && $args['Key'] === '/data/audio/123/resampled.mp3'
                && is_resource($args['Body']);
        }))->willReturn(true);

        $this->upload($audioEntity, $source)->shouldReturn(true);
    }

    public function it_should_upload_from_data()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $this->s3ClientMock->putObject(Argument::that(function ($args) {
            return $args['Bucket'] === 'test-bucket'
                && $args['Key'] === '/data/audio/123/resampled.mp3'
                && $args['Body'] === 'test-content';
        }))->willReturn(true);

        $this->upload($audioEntity, null, 'test-content')->shouldReturn(true);
    }

    public function it_should_get_client_side_upload_url(OciResponse $responseMock)
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $responseMock->getJson()->willReturn((object)[
            'fullPath' => 'https://test-upload-url'
        ]);

        $this->objectStorageClientMock->createPreauthenticatedRequest(Argument::that(function ($args) {
            return $args['namespaceName'] === 'test-namespace'
                && $args['bucketName'] === 'test-bucket'
                && $args['createPreauthenticatedRequestDetails']['name'] === '/data/audio/123/source'
                && $args['createPreauthenticatedRequestDetails']['objectName'] === '/data/audio/123/source'
                && $args['createPreauthenticatedRequestDetails']['accessType'] === 'ObjectWrite'
                && is_string($args['createPreauthenticatedRequestDetails']['timeExpires']);
        }))->willReturn($responseMock);

        $this->getClientSideUploadUrl($audioEntity)->shouldReturn('https://test-upload-url');
    }
}
