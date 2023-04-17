<?php
declare(strict_types=1);

namespace Minds\Core\Suggestions\DefaultTagMapping;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Suggestions\DefaultTagMapping\Repository;

/*
 * Manager for getting defaulted suggestions relevant specified tags.
 * These relations are currently predefined and manual.
 */
class Manager
{
    public function __construct(
        private ?Repository $repository = null,
        private ?Logger $logger = null,
        private ?PsrWrapper $cache = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->logger ??= Di::_()->get('Logger');
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Get default suggestions based upon a users tags.
     * @param string $entityType - type of the entities we are requestion suggestions of.
     * @param array $tags - tags we want to get suggestions for.
     * @return array suggestions.
     */
    public function getSuggestions(string $entityType = 'group', array $tags = []): array
    {
        $suggestions = [];

        try {
            $suggestions = iterator_to_array($this->repository->getList(
                entityType: $entityType,
                tags: $tags
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
        $cacheKey = 'fallback_default_tag_suggestions:' . $entityType;
        $suggestions = unserialize($this->cache->get($cacheKey));
        if (!$suggestions) {
            $suggestions = iterator_to_array($this->repository->getList(
                entityType: $entityType
            ));
            $this->cache->set($cacheKey, serialize($suggestions));
        }
        return $suggestions;
    }
}
