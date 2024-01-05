<?php
namespace Minds\Core\Comments\EmbeddedComments\Services;

use Minds\Core\Comments\EmbeddedComments\Models\EmbeddedCommentsSettings;
use Minds\Core\Comments\EmbeddedComments\Repositories\EmbeddedCommentsSettingsRepository;
use Psr\SimpleCache\CacheInterface;

class EmbeddedCommentsSettingsService
{
    public function __construct(
        private EmbeddedCommentsSettingsRepository $repository,
        private CacheInterface $cache,
    ) {
        
    }

    /**
     * Returns all the settings for a user
     */
    public function getSettings(int $ownerGuid, bool $useCache = true): ?EmbeddedCommentsSettings
    {
        $cacheKey = $this->buildCacheKey($ownerGuid);

        if ($useCache && $cached = $this->cache->get($cacheKey)) {
            return unserialize($cached);
        }

        $settings = $this->repository->getSettings($ownerGuid);

        $this->cache->set($cacheKey, serialize($settings));

        return $settings;
    }

    /**
     * Saves user settings and clears the cache
     */
    public function setSettings(EmbeddedCommentsSettings $settings): bool
    {
        $success = $this->repository->setSettings($settings);

        if ($success) {
            $cacheKey = $this->buildCacheKey($settings->userGuid);
            $this->cache->delete($cacheKey);
        }

        return $success;
    }

    /**
     * Returns a cache key for the user
     */
    private function buildCacheKey(int $ownerGuid): string
    {
        return 'embedded-comments-plugin:settings:' . $ownerGuid;
    }
}
