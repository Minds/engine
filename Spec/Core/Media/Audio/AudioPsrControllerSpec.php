<?php

namespace Spec\Minds\Core\Media\Audio;

use Minds\Core\Media\Audio\AudioPsrController;
use Minds\Core\Media\Audio\AudioEntity;
use Minds\Core\Media\Audio\AudioAssetStorageService;
use Minds\Core\Config\Config;
use Minds\Core\Media\Audio\AudioService;
use Minds\Core\Media\Audio\AudioThumbnailService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\TextResponse;

class AudioPsrControllerSpec extends ObjectBehavior
{
    protected Collaborator $audioServiceMock;
    protected Collaborator $audioThumbnailServiceMock;
    protected Collaborator $aclMock;

    public function let(
        AudioService $audioServiceMock,
        AudioThumbnailService $audioThumbnailServiceMock,
        ACL $aclMock
    ) {
        $this->audioServiceMock = $audioServiceMock;
        $this->audioThumbnailServiceMock = $audioThumbnailServiceMock;
        $this->aclMock = $aclMock;

        $this->beConstructedWith($audioServiceMock, $audioThumbnailServiceMock, $aclMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AudioPsrController::class);
    }

    public function it_should_redirect_to_download_url(
        ServerRequestInterface $requestMock,
        AudioEntity $audioEntityMock
    ) {
        $requestMock->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn([
                'guid' => '123'
            ]);

        $this->audioServiceMock->getByGuid(123)
            ->shouldBeCalled()
            ->willReturn($audioEntityMock);

        $this->aclMock->read($audioEntityMock)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->audioServiceMock->getDownloadUrl($audioEntityMock)
            ->willReturn('https://test-upload-url');

        $response = $this->downloadAudioAsset($requestMock);
        $response->shouldBeAnInstanceOf(RedirectResponse::class);
        $response->getStatusCode()->shouldBe(302);
        $response->getHeaderLine('Location')->shouldBe('https://test-upload-url');
    }

    public function it_should_not_allow_acl(
        ServerRequestInterface $requestMock,
        AudioEntity $audioEntityMock
    ) {
        $requestMock->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn([
                'guid' => '123'
            ]);

        $this->audioServiceMock->getByGuid(123)
            ->shouldBeCalled()
            ->willReturn($audioEntityMock);

        $this->aclMock->read($audioEntityMock)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->audioServiceMock->getDownloadUrl($audioEntityMock)
            ->shouldNotBeCalled();

        $this->shouldThrow(ForbiddenException::class)->duringDownloadAudioAsset($requestMock);
    }

    public function it_should_return_a_thumbnail(
        ServerRequestInterface $requestMock,
        AudioEntity $audioEntityMock
    ) {
        $requestMock->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn([
                'guid' => '123'
            ]);

        $this->audioServiceMock->getByGuid(123)
            ->shouldBeCalled()
            ->willReturn($audioEntityMock);

        $this->aclMock->read($audioEntityMock)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->audioThumbnailServiceMock->get($audioEntityMock)
            ->willReturn('image-data');

        $response = $this->getThumbnail($requestMock);
        $response->shouldBeAnInstanceOf(TextResponse::class);
        $response->getStatusCode()->shouldBe(200);
        $response->getBody()->getContents()->shouldBe('image-data');
    }

    public function it_shoul_not_return_a_thumbnail_if_permission_denied(
        ServerRequestInterface $requestMock,
        AudioEntity $audioEntityMock
    ) {
        $requestMock->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn([
                'guid' => '123'
            ]);

        $this->audioServiceMock->getByGuid(123)
            ->shouldBeCalled()
            ->willReturn($audioEntityMock);

        $this->aclMock->read($audioEntityMock)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->audioThumbnailServiceMock->get($audioEntityMock)
            ->shouldNotBeCalled();
    
        $this->shouldThrow(ForbiddenException::class)->duringGetThumbnail($requestMock);
    }
}
