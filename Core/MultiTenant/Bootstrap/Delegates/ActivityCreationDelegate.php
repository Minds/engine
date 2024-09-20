<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Entities\Activity;
use Minds\Core\Feeds\Activity\Manager as ActivityManager;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MetadataExtractor;
use Minds\Core\Security\ACL;
use Minds\Entities\User;

/**
 * Delegate for creating activities from a given set of parsed articles.
 */
class ActivityCreationDelegate
{
    public function __construct(
        private ActivityManager $activityManager,
        private MetadataExtractor $metadataExtractor,
        private ACL $acl,
        private Logger $logger
    ) {
    }

    /**
     * Create activities from a given set of parsed articles.
     * @param array $articles - The parsed articles.
     * @param User $user - The user to create the activities for.
     * @return void
     */
    public function onBulkCreate(array $articles, User $user): void
    {
        $ignore = $this->acl::$ignore;
        $this->acl::$ignore = true;

        foreach ($articles as $item) {
            try {
                if ($item['link']) {
                    try {
                        $item['image'] = $this->extractThumbnailUrl($item['link']);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }

                $activity = new Activity();
                $activity->setMessage($item['description'] ? rawurldecode($item['description']) : null);
                $activity->setTags($item['hashtags'] ?? []);
                $activity->setLinkTitle($item['title'] ?? '');
                $activity->setUrl($item['link'] ?? '');
                $activity->setBlurb($item['description'] ?? '');
                $activity->setThumbnail($item['image'] ?? '');
                $activity->setContainerGUID($user->getGuid());
                $activity->owner_guid = $user->getGuid();
                $activity->setOwner($user->export());

                $this->activityManager->add($activity);
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }

        $this->acl::$ignore = $ignore;
    }

    /**
     * Extract the website logo from a given URL.
     * @param string $url - The URL of the site.
     * @return string - The URL of the logo.
     */
    private function extractThumbnailUrl(string $url): string
    {
        return $this->metadataExtractor->extractThumbnailUrl(
            siteUrl: $url,
        ) ?? '';
    }
}
