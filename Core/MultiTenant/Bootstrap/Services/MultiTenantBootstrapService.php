<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateUserNameDelegate;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Core\MultiTenant\Bootstrap\Models\ExtractedMetadata;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;
use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\ConfigGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\ContentGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\LogoGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MarkdownExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MetadataExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ScreenshotExtractor;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 * Root service for bootstrapping a tenant.
 */
class MultiTenantBootstrapService
{
    public function __construct(
        private MultiTenantBootService $multiTenantBootService,
        private BootstrapProgressRepository $progressRepository,
        private MarkdownExtractor $markdownExtractor,
        private ScreenshotExtractor $screenshotExtractor,
        private MetadataExtractor $metadataExtractor,
        private LogoGenerationHandler $logoGenerationHandler,
        private ConfigGenerationHandler $configGenerationHandler,
        private ContentGenerationHandler $contentGenerationHandler,
        private UpdateUserNameDelegate $updateUserNameDelegate,
        private EntitiesBuilder $entitiesBuilder,
        private Logger $logger
    ) {
    }

    /**
     * Bootstrap a tenant.
     * @param string $siteUrl - The URL of the site to bootstrap.
     * @param int $tenantId - The ID of the tenant to bootstrap.
     * @return void
     * @throws ServerErrorException
     */
    public function bootstrap(
        string $siteUrl,
        int $tenantId
    ): void {
        $tenant = $this->bootFromTenantId($tenantId);
        $rootUser = $this->entitiesBuilder->single($tenant->rootUserGuid);

        if (!$rootUser || !$rootUser instanceof User) {
            throw new ServerErrorException('Root user not found');
        }

        try {
            $screenshotBlob = $this->screenshotExtractor->extract($siteUrl);
            $markdownContent = $this->markdownExtractor->extract($siteUrl);
            $metadata = $this->metadataExtractor->extract($siteUrl);

            // We should set this with the username during tenant creation in future.
            if ($metadata?->getPublisher()) {
                $this->handleUserNameUpdate($rootUser, $metadata->getPublisher());
            }

            $this->handleConfigs(screenshotBlob: $screenshotBlob, metadata: $metadata);
            $this->handleLogos($siteUrl);
            $this->handleContent(markdownContent: $markdownContent, rootUser: $rootUser);
        } catch (\Exception $e) {
            $this->logger->error("Error during bootstrap process: " . $e->getMessage());
            throw new ServerErrorException("Failed to bootstrap tenant: " . $e->getMessage());
        } finally {
            $this->progressRepository->updateProgress(BootstrapStepEnum::FINISHED, true);
            $this->logger->info("Done bootstrapping for tenant with ID: $tenantId, updated progress to finished\n\n");
        }
    }

    /**
     * Boot into a Tenant from its ID.
     * @param int $tenantId - The ID of the Tenant to boot.
     * @return Tenant - The Tenant that was booted into.
     * @throws ServerErrorException
     */
    private function bootFromTenantId(int $tenantId): Tenant
    {
        $this->logger->info("Bootstrapping tenant with ID: $tenantId");
        $this->multiTenantBootService->bootFromTenantId($tenantId);
        $tenant = $this->multiTenantBootService->getTenant();

        if (!$tenant) {
            throw new ServerErrorException('No tenant found');
        }

        return $tenant;
    }

    /**
     * Handle the configuration generation.
     * @param ?string $screenshotBlob - The screenshot blob.
     * @param ?ExtractedMetadata $metadata - The metadata.
     * @return void
     */
    private function handleConfigs(
        ?string $screenshotBlob,
        ?ExtractedMetadata $metadata
    ) {
        $this->configGenerationHandler->handle(
            screenshotBlob: $screenshotBlob,
            description: $metadata?->getDescription() ?? null,
            siteName: $metadata?->getPublisher() ?? null
        );
    }

    /**
     * Handle the logo generation.
     * @param string $siteUrl - The URL of the site.
     * @return void
     */
    private function handleLogos(string $siteUrl)
    {
        $this->logoGenerationHandler->handle($siteUrl);
    }

    /**
     * Handle the content generation.
     * @param ?string $markdownContent - The markdown content.
     * @param User $rootUser - The root user.
     * @return void
     */
    private function handleContent(?string $markdownContent, User $rootUser)
    {
        if ($markdownContent) {
            $this->contentGenerationHandler->handle(
                markdownContent: $markdownContent,
                rootUser: $rootUser
            );
        } else {
            $this->logger->info("No markdown content found, skipping content generation");
        }
    }

    /**
     * Handle the user name update.
     * @param User $rootUser - The root user.
     * @param string $publisherName - The publisher name.
     * @return void
     */
    private function handleUserNameUpdate(User $rootUser, string $publisherName)
    {
        try {
            $name = ucfirst($publisherName);

            // Remove any TLD's from the publisher name.
            if (str_contains($name, '.')) {
                $name = explode('.', $name)[0];
            }

            $this->logger->info("Updating user name to: $name");
            $this->updateUserNameDelegate->onUpdate($rootUser, $name);
            $this->logger->info("User name updated successfully");
        } catch (\Exception $e) {
            $this->logger->error("Failed to update user name: " . $e->getMessage());
        }
    }
}
