<?php
namespace Minds\Core\Hashtags\Trending;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config as ConfigConfig;
use Minds\Core\Di\Di;
use Minds\Interfaces\BasicCacheInterface;

/**
 * Manager for use in retrieving trending hashtags. Hashtags are determined to be trending
 * based heavily on the repositories response, however this manager handles exclusion of
 * the previous days tags to keep tags fresh.
 */
class Manager implements ManagerInterface
{
    public function __construct(
        private ?Repository $repository = null,
        private ?BasicCacheInterface $cache = null,
        private ?ConfigConfig $config = null
    ) {
        $this->repository = $repository ?? Di::_()->get('Hashtags\Trending\Repository');
        $this->cache = $cache ?? Di::_()->get('Hashtags\Trending\Cache');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Gets array of trending hashtags, excluding the previous days trending hashtags.
     * @return array - array of tags alongside their votes and posts counts.
     */
    public function getCurrentlyTrendingHashtags(): array
    {
        if (
            !$this->config->get('trending_tags_development_mode') &&
            count($cached = $this->cache->get())
        ) {
            return $cached;
        }

        $previouslyTrending = $this->getPreviouslyTrending();
        $dailyTrending = $this->getDailyTrending($previouslyTrending);

        $currentlyTrending = $this->mapToLegacyTagFormat($dailyTrending);

        $this->cache->set($currentlyTrending);

        return $currentlyTrending;
    }

    /**
     * Gets array of previous days trending hashtags (between 24 and 48 hours ago).
     * @return array - array of tag names. e.g. ['hashtag1', 'hashtag2'].
     */
    protected function getPreviouslyTrending(): array
    {
        $from = strtotime('-48 hours', time());
        $to = strtotime('-24 hours', time());

        if ($this->config->get('trending_tags_development_mode')) {
            $from = strtotime('-2 years', time());
            $to = strtotime('-1 year', time());
        }

        $response = $this->repository->getList([
            'from' => $from,
            'to' => $to,
        ]);

        return $this->getTagNameArrayFromResponse($response);
    }

    /**
     * Gets array of currently trending hashtags in the last 24 hours.
     * @param array $previousDaysTrending - tags to exclude - intended to be used to exclude the previous days tags.
     * @return array - array of tags from response.
     */
    protected function getDailyTrending(array $excludeTags = []): array
    {
        $from = strtotime('-24 hours', time());

        if ($this->config->get('trending_tags_development_mode')) {
            $from = strtotime('-1 year', time());
        }

        $response = $this->repository->getList([
            'from' => $from,
            'exclude_tags' => $excludeTags
        ]);

        return $response->toArray();
    }

    /**
     * Parses tag names from a response object.
     * @param Response $response - response object to parse names from.
     * @return array - array of tag names. e.g. ['hashtag1', 'hashtag2'].
     */
    protected function getTagNameArrayFromResponse(Response $response): array
    {
        return array_map(
            function ($tag) {
                return $tag['tag'];
            },
            $response->toArray()
        ) ?? [];
    }

    /**
     * Map to legacy output format to make data consumption simpler.
     * @param array $tags array of tags from response.
     * @return array - array of tags in legacy format.
     */
    protected function mapToLegacyTagFormat(array $tags): array
    {
        return array_map(function ($tag) {
            $object = [];
            $object['selected'] = false;
            $object['value'] = $tag['tag'];
            $object['posts_count'] = $tag['posts'] ?? 0;
            $object['votes_count'] = $tag['votes'] ?? 0;
            $object['type'] = 'trending';
            return $object;
        }, $tags);
    }
}
