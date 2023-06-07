<?php
declare(strict_types=1);

namespace Minds\Core\Suggestions\DefaultTagMapping;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Core\Log\Logger;
use Minds\Core\Suggestions\DefaultTagMapping\Repository;
use Minds\Entities\User;

/*
 * Manager for getting defaulted suggestions relevant specified tags.
 * These relations are currently predefined and manual.
 */
class Manager
{
    public function __construct(
        private ?Repository $repository = null,
        private ?UserHashtagsManager $hashtagManager = null,
        private ?Logger $logger = null,
        private ?PsrWrapper $cache = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->hashtagManager ??= Di::_()->get('Hashtags\User\Manager');
        $this->logger ??= Di::_()->get('Logger');
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Get default suggestions based upon a users tags.
     * @param string $entityType - type of the entities we are requestion suggestions of.
     * @param User $user - user to get tags for.
     * @return array suggestions.
     */
    public function getSuggestions(string $entityType = 'group', User $user = null): array
    {
        $suggestions = [];
        $userTags = [];
        
        if ($user) {
            $userTags = $this->getUserDiscoveryTags($user);
        }
        
        try {
            $suggestions = iterator_to_array($this->repository->getList(
                entityType: $entityType,
                tags: $userTags
            ));
        } catch (\Exception $e) {
            $this->logger->error($e); // fallback to default fallback tag list on error.
        }

        if (!count($suggestions)) {
            try {
                $suggestions = $this->getFallbackSuggestions($entityType);
            } catch (\Exception $e) {
                $this->logger->error($e);
            }
        }

        shuffle($suggestions);
        return $suggestions;
    }

    /**
     * Get fallbacks suggestions for users with no tags, or to be shown on error.
     * @param string $entityType - type of the entities we are requestion suggestions of.
     * @throws \Exception on error.
     * @return array suggestions.
     */
    private function getFallbackSuggestions(string $entityType): array
    {
        // try get get existing value from cache.
        $cacheKey = 'fallback_default_tag_suggestions:' . $entityType;
        $cachedSuggestions = $this->cache->get($cacheKey);
        $suggestions = $cachedSuggestions ? unserialize($this->cache->get($cacheKey)) : null;

        // if not cached, get from DB and store in cache.
        if (!$suggestions) {
            $suggestions = iterator_to_array($this->repository->getList(
                entityType: $entityType
            ));
            $this->cache->set($cacheKey, serialize($suggestions), 86400);
        }
        return $suggestions;
    }

    /**
     * Gets a users discovery tags.
     * @param array $user - user to get tags for.
     * @return array string array of tags.
     */
    private function getUserDiscoveryTags($user): array
    {
        $tags = $this->hashtagManager->setUser($user)->get([
            'trending' => false,
            'defaults' => false
        ]);

        if (!$tags) {
            return [];
        }

        return array_map(function ($tag) {
            return strtolower($tag['value']);
        }, $tags);
    }
}
