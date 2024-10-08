<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services;

use Minds\Core\MultiTenant\Bootstrap\Services\MultiTenantBootstrapService;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;
use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\ConfigGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\ContentGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\LogoGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MarkdownExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MetadataExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ScreenshotExtractor;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateUserNameDelegate;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Core\MultiTenant\Bootstrap\Models\ExtractedMetadata;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Common\Traits\TenantFactoryMockBuilder;

class MultiTenantBootstrapServiceSpec extends ObjectBehavior
{
    use TenantFactoryMockBuilder;

    private $multiTenantBootServiceMock;
    private $progressRepositoryMock;
    private $markdownExtractorMock;
    private $screenshotExtractorMock;
    private $metadataExtractorMock;
    private $logoGenerationHandlerMock;
    private $configGenerationHandlerMock;
    private $contentGenerationHandlerMock;
    private $updateUserNameDelegateMock;
    private $entitiesBuilderMock;
    private $loggerMock;

    public function let(
        MultiTenantBootService $multiTenantBootService,
        BootstrapProgressRepository $progressRepository,
        MarkdownExtractor $markdownExtractor,
        ScreenshotExtractor $screenshotExtractor,
        MetadataExtractor $metadataExtractor,
        LogoGenerationHandler $logoGenerationHandler,
        ConfigGenerationHandler $configGenerationHandler,
        ContentGenerationHandler $contentGenerationHandler,
        UpdateUserNameDelegate $updateUserNameDelegate,
        EntitiesBuilder $entitiesBuilder,
        Logger $logger
    ) {
        $this->multiTenantBootServiceMock = $multiTenantBootService;
        $this->progressRepositoryMock = $progressRepository;
        $this->markdownExtractorMock = $markdownExtractor;
        $this->screenshotExtractorMock = $screenshotExtractor;
        $this->metadataExtractorMock = $metadataExtractor;
        $this->logoGenerationHandlerMock = $logoGenerationHandler;
        $this->configGenerationHandlerMock = $configGenerationHandler;
        $this->contentGenerationHandlerMock = $contentGenerationHandler;
        $this->updateUserNameDelegateMock = $updateUserNameDelegate;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->loggerMock = $logger;

        $this->beConstructedWith(
            $multiTenantBootService,
            $progressRepository,
            $markdownExtractor,
            $screenshotExtractor,
            $metadataExtractor,
            $logoGenerationHandler,
            $configGenerationHandler,
            $contentGenerationHandler,
            $updateUserNameDelegate,
            $entitiesBuilder,
            $logger
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MultiTenantBootstrapService::class);
    }

    public function it_should_bootstrap_tenant(User $rootUser, ExtractedMetadata $metadata)
    {
        $siteUrl = 'https://example.minds.com';
        $tenantId = 1;
        $rootUserGuid = 1234567890123456;
        $tenant = $this->generateTenantMock(rootUserGuid: $rootUserGuid);
        $publisher = 'Minds';
        $description = 'Description';

        $metadata->getPublisher()
            ->shouldBeCalled()
            ->willReturn($publisher);

        $metadata->getDescription()
            ->shouldBeCalled()
            ->willReturn($description);

        $this->multiTenantBootServiceMock->resetRootConfigs()->shouldBeCalled();
        $this->multiTenantBootServiceMock->bootFromTenantId($tenantId)->shouldBeCalled();
        $this->multiTenantBootServiceMock->getTenant()->willReturn($tenant);
        $this->entitiesBuilderMock->single($tenant->rootUserGuid)->willReturn($rootUser);

        $this->screenshotExtractorMock->extract($siteUrl)->willReturn('screenshot-blob');
        $this->markdownExtractorMock->extract($siteUrl)->willReturn('markdown-content');
        $this->metadataExtractorMock->extract($siteUrl)->willReturn($metadata);

        $this->configGenerationHandlerMock->handle(
            screenshotBlob: 'screenshot-blob',
            description: $description,
            siteName: $publisher
        )->shouldBeCalled();

        $this->logoGenerationHandlerMock->handle($siteUrl)->shouldBeCalled();
        $this->contentGenerationHandlerMock->handle(
            markdownContent: 'markdown-content',
            rootUser: $rootUser
        )->shouldBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::FINISHED, true)#
            ->shouldBeCalled()
            ->willReturn(true);
        $this->loggerMock->info(Argument::any())->shouldBeCalled();

        $this->bootstrap($siteUrl, $tenantId);
    }

    public function it_should_bootstrap_tenant_while_stripping_tld_from_publisher_name(User $rootUser, ExtractedMetadata $metadata)
    {
        $siteUrl = 'https://example.minds.com';
        $tenantId = 1;
        $rootUserGuid = 1234567890123456;
        $tenant = $this->generateTenantMock(rootUserGuid: $rootUserGuid);
        $publisher = 'Minds.com';
        $description = 'Description';

        $metadata->getPublisher()
            ->shouldBeCalled()
            ->willReturn($publisher);

        $metadata->getDescription()
            ->shouldBeCalled()
            ->willReturn($description);

        $this->multiTenantBootServiceMock->resetRootConfigs()->shouldBeCalled();
        $this->multiTenantBootServiceMock->bootFromTenantId($tenantId)->shouldBeCalled();
        $this->multiTenantBootServiceMock->getTenant()->willReturn($tenant);
        $this->entitiesBuilderMock->single($tenant->rootUserGuid)->willReturn($rootUser);

        $this->screenshotExtractorMock->extract($siteUrl)->willReturn('screenshot-blob');
        $this->markdownExtractorMock->extract($siteUrl)->willReturn('markdown-content');
        $this->metadataExtractorMock->extract($siteUrl)->willReturn($metadata);

        $this->updateUserNameDelegateMock->onUpdate($rootUser, 'Minds')
            ->shouldBeCalled();

        $this->configGenerationHandlerMock->handle(
            screenshotBlob: 'screenshot-blob',
            description: $description,
            siteName: $publisher
        )->shouldBeCalled();

        $this->logoGenerationHandlerMock->handle($siteUrl)->shouldBeCalled();
        $this->contentGenerationHandlerMock->handle(
            markdownContent: 'markdown-content',
            rootUser: $rootUser
        )->shouldBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::FINISHED, true)#
            ->shouldBeCalled()
            ->willReturn(true);
        $this->loggerMock->info(Argument::any())->shouldBeCalled();

        $this->bootstrap($siteUrl, $tenantId);
    }

    public function it_should_handle_errors_during_bootstrap(User $rootUser)
    {
        $siteUrl = 'https://example.minds.com';
        $tenantId = 1;
        $rootUserGuid = 1234567890123456;
        $tenant = $this->generateTenantMock(rootUserGuid: $rootUserGuid);

        $this->multiTenantBootServiceMock->resetRootConfigs()->shouldBeCalled();
        $this->multiTenantBootServiceMock->bootFromTenantId($tenantId)->shouldBeCalled();
        $this->multiTenantBootServiceMock->getTenant()->willReturn($tenant);
        $this->entitiesBuilderMock->single($tenant->rootUserGuid)->willReturn($rootUser);

        $this->screenshotExtractorMock->extract($siteUrl)->willThrow(new \Exception('Error'));

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::FINISHED, true)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->shouldThrow(ServerErrorException::class)->during('bootstrap', [$siteUrl, $tenantId]);
    }
}
