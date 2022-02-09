<?php
namespace Minds\Core\Hashtags\Trending;

/**
 * Manager for use in retrieving trending hashtags.
 */
interface ManagerInterface
{
    public function __construct(?Repository $repository);

    /**
     * Gets array of trending hashtags.
     * @return array - array of tags.
     */
    public function getCurrentlyTrendingHashtags(): array;
}
