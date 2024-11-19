<?php

namespace Spec\Minds\Core\Media\Audio;

use Exception;
use Minds\Core\Media\Audio\AudioAssetStorageService;
use Minds\Core\Media\Audio\AudioEntity;
use Minds\Core\Media\Audio\AudioThumbnailService;
use Minds\Core\Media\Imagick\Manager as ImagickManager;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class AudioThumbnailServiceSpec extends ObjectBehavior
{
    private Collaborator $audioAssetStorageServiceMock;
    private Collaborator $imagickManagerMock;

    public function let(
        AudioAssetStorageService $audioAssetStorageServiceMock,
        ImagickManager $imagickManagerMock,
    ) {
        $this->beConstructedWith($audioAssetStorageServiceMock, $imagickManagerMock);
        $this->audioAssetStorageServiceMock = $audioAssetStorageServiceMock;
        $this->imagickManagerMock = $imagickManagerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AudioThumbnailService::class);
    }

    public function it_should_process_thumnail()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456,
        );

        $this->imagickManagerMock->setImageFromBlob(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIAQMAAAD+wSzIAAAABlBMVEX///+/v7+jQ3Y5AAAADklEQVQI12P4AIX8EAgALgAD/aNpbtEAAAAASUVORK5CYII', true))
            ->shouldBeCalled()
            ->willReturn($this->imagickManagerMock);
        $this->imagickManagerMock->getJpeg()->willReturn('jpeg');

        $this->audioAssetStorageServiceMock->upload($audioEntity, null, 'jpeg', 'thumbnail.jpg')
            ->shouldBeCalled();

        $this->process($audioEntity, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIAQMAAAD+wSzIAAAABlBMVEX///+/v7+jQ3Y5AAAADklEQVQI12P4AIX8EAgALgAD/aNpbtEAAAAASUVORK5CYII');
    }

    public function it_should_get_thumbnail()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456,
        );
        $this->audioAssetStorageServiceMock->downloadToMemory($audioEntity, 'thumbnail.jpg')
            ->shouldBeCalled()
            ->willReturn('img');

        $this->get($audioEntity)->shouldBe('img');
    }

    public function it_should_return_default_image_if_none_found()
    {
        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456,
        );
        $this->audioAssetStorageServiceMock->downloadToMemory($audioEntity, 'thumbnail.jpg')
            ->shouldBeCalled()
            ->willThrow(new Exception());

        $this->get($audioEntity)->shouldBe(file_get_contents(__MINDS_ROOT__ . '/Assets/photos/default-audio.jpg'));
    }
}
