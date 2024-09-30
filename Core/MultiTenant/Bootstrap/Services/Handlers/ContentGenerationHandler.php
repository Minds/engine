<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Handlers;

use Minds\Core\MultiTenant\Bootstrap\Delegates\ActivityCreationDelegate;
use Minds\Entities\User;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ContentExtractor;
use Minds\Exceptions\ServerErrorException;

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
     * @param string|null $markdownContent - The markdown content to generate content from.
     * @param User $rootUser - The root user to create activities for.
     */
    public function handle(string $markdownContent = null, User $rootUser)
    {
        try {
            if ($markdownContent) {
                $this->logger->info("Extracting content...");

                $content = $this->contentExtractor->extract($markdownContent);
            
                $this->logger->info("Generated content: " . json_encode($content));

                if (count($content['articles'])) {
                    $this->activityCreationDelegate->onBulkCreate($content['articles'], $rootUser);
                    $this->progressRepository->updateProgress(BootstrapStepEnum::CONTENT_STEP, true);
                    $this->logger->info("Updated bootstrap progress for content step to success");
                } else {
                    throw new ServerErrorException("No articles generated");
                }
            } else {
                throw new ServerErrorException("No markdown content provided");
            }
        } catch (\Exception $e) {
            $this->logger->error("Error extracting and setting content: " . $e->getMessage());
            $this->progressRepository->updateProgress(BootstrapStepEnum::CONTENT_STEP, false);
            $this->logger->info("Updated bootstrap progress for content step to failed");
        }
    }
}
