<?php

namespace Spec\Minds\Core\Pro\Assets;

use ElggFile;
use Minds\Core\Media\Imagick\Manager as ImageManager;
use Minds\Core\Pro\Assets\Asset;
use Minds\Core\Pro\Assets\Manager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\UploadedFile;

class ManagerSpec extends ObjectBehavior
{
    /** @var ImageManager */
    protected $imageManager;

    public function let(
        ImageManager $imageManager
    ) {
        $this->imageManager = $imageManager;
        $this->beConstructedWith($imageManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_set_logo(
        User $user,
        UploadedFile $file,
        StreamInterface $fileStream,
        Asset $asset,
        ElggFile $assetFile
    ) {
        $file->getStream()
            ->shouldBeCalled()
            ->willReturn($fileStream);

        $fileStream->getContents()
            ->shouldBeCalled()
            ->willReturn('~image file~');

        $file->getClientFilename()
            ->shouldBeCalled()
            ->willReturn('asset.img');

        $this->imageManager->setImageFromBlob('~image file~', 'asset.img')
            ->shouldBeCalled()
            ->willReturn();

        $asset->setType('logo')
            ->shouldBeCalled()
            ->willReturn($asset);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $asset->setUserGuid(1000)
            ->shouldBeCalled()
            ->willReturn($asset);

        $this->imageManager->resize(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->imageManager);

        $this->imageManager->getPng()
            ->shouldBeCalled()
            ->willReturn('~png file~');

        $asset->getFile()
            ->shouldBeCalled()
            ->willReturn($assetFile);

        $assetFile->open('write')
            ->shouldBeCalled()
            ->willReturn(true);

        $assetFile->write('~png file~')
            ->shouldBeCalled()
            ->willReturn(true);

        $assetFile->close()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setType('logo')
            ->setUser($user)
            ->setActor($user)
            ->set($file, $asset)
            ->shouldReturn(true);
    }

    public function it_should_set_background(
        User $user,
        UploadedFile $file,
        StreamInterface $fileStream,
        Asset $asset,
        ElggFile $assetFile
    ) {
        $file->getStream()
            ->shouldBeCalled()
            ->willReturn($fileStream);

        $fileStream->getContents()
            ->shouldBeCalled()
            ->willReturn('~image file~');

        $file->getClientFilename()
            ->shouldBeCalled()
            ->willReturn('asset.img');

        $this->imageManager->setImageFromBlob('~image file~', 'asset.img')
            ->shouldBeCalled()
            ->willReturn();

        $asset->setType('background')
            ->shouldBeCalled()
            ->willReturn($asset);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $asset->setUserGuid(1000)
            ->shouldBeCalled()
            ->willReturn($asset);

        $this->imageManager->autorotate()
            ->shouldBeCalled()
            ->willReturn($this->imageManager);

        $this->imageManager->resize(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($this->imageManager);

        $this->imageManager->getJpeg(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn('~jpg file~');

        $asset->getFile()
            ->shouldBeCalled()
            ->willReturn($assetFile);

        $assetFile->open('write')
            ->shouldBeCalled()
            ->willReturn(true);

        $assetFile->write('~jpg file~')
            ->shouldBeCalled()
            ->willReturn(true);

        $assetFile->close()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setType('background')
            ->setUser($user)
            ->setActor($user)
            ->set($file, $asset)
            ->shouldReturn(true);
    }
}
