<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Channels;

use ElggFile;
use Minds\Core\Channels\BannerService;
use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Di\Di;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class BannerServiceSpec extends ObjectBehavior
{
    private Collaborator $imagickManager;
    private Collaborator $saveAction;
    private Collaborator $config;
    private Collaborator $logger;

    public function let(
        ImagickManager $imagickManager,
        SaveAction $saveAction,
        Config $config,
        Logger $logger
    ) {
        $this->beConstructedWith($imagickManager, $saveAction, $config, $logger);
        $this->imagickManager = $imagickManager;
        $this->saveAction = $saveAction;
        $this->config = $config;
        $this->logger = $logger;

        Di::_()->bind('Storage\S3', function ($di) {
            return new class {
                public function __construct()
                {
                }
                public function write()
                {
                    return $this;
                }
                public function open()
                {
                    return $this;
                }
                public function close()
                {
                    return $this;
                }
            };
        });
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BannerService::class);
    }

    // getFile

    public function it_should_get_a_file_for_tenant()
    {
        $entityGuid = (int) Guid::build();

        $expectedFile = new ElggFile();
        $expectedFile->setFilename("banners/{$entityGuid}.jpg");
        $expectedFile->owner_guid = $entityGuid;

        $this->getFile($entityGuid)
            ->shouldBeLike($expectedFile);
    }

    // upload

    public function it_should_upload_a_file_for_a_tenant()
    {
        $user = new User();
        $path = '/path/to/file.jpg';

        $this->imagickManager->setImage($path)
            ->shouldBeCalled()
            ->willReturn($this->imagickManager);

        $this->imagickManager->autorotate()
            ->shouldBeCalled()
            ->willReturn($this->imagickManager);

        $this->imagickManager->resize(2000, 10000)
            ->shouldBeCalled()
            ->willReturn($this->imagickManager);

        $this->imagickManager->getJpeg()
            ->shouldBeCalled()
            ->willReturn('jpeg');

        $this->saveAction->setEntity($user)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->withMutatedAttributes(['icontime'])
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->save(true)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->upload($path, $user)
            ->shouldBe(true);
    }

    public function it_should_log_error_when_exception_is_thrown_when_uploading()
    {
        $user = new User();
        $path = '/path/to/file.jpg';
        $exception = new \Exception('~error message~');

        $this->imagickManager->setImage($path)
            ->shouldBeCalled()
            ->willThrow($exception);

        $this->logger->error($exception)
            ->shouldBeCalled();

        $this->upload($path, $user)
            ->shouldBe(false);
    }
}
