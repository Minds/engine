<?php

namespace Spec\Minds\Core\Chat\Services;

use Aws\S3\S3Client;
use Minds\Core\Config\Config;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\Psr7\Stream;
use Minds\Core\Chat\Services\ChatImageStorageService;
use PhpSpec\Wrapper\Collaborator;

class ChatImageStorageServiceSpec extends ObjectBehavior
{
    /** @var Collaborator */
    protected Collaborator $configMock;

    /** @var Collaborator */
    protected Collaborator $s3ClientMock;

    public function let(Config $configMock, S3Client $s3ClientMock)
    {
        $this->configMock = $configMock;
        $this->s3ClientMock = $s3ClientMock;

        $this->beConstructedWith($configMock, $s3ClientMock);

        $configMock->get('storage')
            ->willReturn(['oci_bucket_name' => 'test-bucket']);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ChatImageStorageService::class);
    }

    public function it_should_download_image_to_memory(Stream $stream)
    {
        $imageGuid = '123';
        $ownerGuid = '456';
        $imageData = 'test-image-data';

        $stream->getContents()
            ->willReturn($imageData);

        $this->s3ClientMock->getObject(Argument::that(function ($args) {
            return $args['Bucket'] === 'test-bucket'
                && $args['Key'] === '/data/chat/images/123';
        }))
            ->willReturn(['Body' => $stream]);

        $this->downloadToMemory($imageGuid, $ownerGuid)
            ->shouldBe($imageData);
    }

    public function it_should_upload_image(
    ) {
        $imageGuid = '123';
        $ownerGuid = '456';
        $imageData = 'test-image-data';

        $this->s3ClientMock->putObject([
            'Bucket' => 'test-bucket',
            'Key' => '/data/chat/images/123',
            'Body' => $imageData
        ])
            ->shouldBeCalled();

        $this->upload($imageGuid, $ownerGuid, $imageData)
            ->shouldBe(true);
    }

    public function it_should_delete_image(
    ) {
        $imageGuid = '123';
        $ownerGuid = '456';

        $this->s3ClientMock->deleteObject([
            'Bucket' => 'test-bucket',
            'Key' => '/data/chat/images/123'
        ])
            ->shouldBeCalled();

        $this->delete($imageGuid, $ownerGuid)
            ->shouldBe(true);
    }
}
