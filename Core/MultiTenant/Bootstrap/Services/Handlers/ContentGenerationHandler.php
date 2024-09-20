<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Handlers;

use Minds\Core\MultiTenant\Bootstrap\Delegates\ActivityCreationDelegate;
use Minds\Entities\User;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ContentExtractor;

/**
 * Handles the generation of content.
 */
class ContentGenerationHandler
{
    public function __construct(
        private ContentExtractor $contentExtractor,
        private ActivityCreationDelegate $activityCreationDelegate,
        private BootstrapProgressRepository $progressRepository,
        private Logger $logger
    ) {
    }

    /**
     * Handles the generation of content.
     * @param string $markdownContent - The markdown content to generate content from.
     * @param User $rootUser - The root user to create activities for.
     */
    public function handle(string $markdownContent, User $rootUser)
    {
        try {
            $this->logger->info("Extracting content...");

            $content = $this->contentExtractor->extract($markdownContent);
            $this->logger->info("Generated content: " . json_encode($content));

            if (count($content['articles'])) {
                $this->activityCreationDelegate->onBulkCreate($content['articles'], $rootUser);
                $this->logger->info("Content updated");
            }

            $this->progressRepository->updateProgress(BootstrapStepEnum::CONTENT_STEP, true);
            $this->logger->info("Updated bootstrap progress for content step to success");
        } catch (\Exception $e) {
            $this->logger->error("Error extracting and setting content: " . $e->getMessage());
            $this->progressRepository->updateProgress(BootstrapStepEnum::CONTENT_STEP, false);
            $this->logger->info("Updated bootstrap progress for content step to failed");
        }
    }
}
