<?php

namespace Minds\Core\Hashtags\User;

use Minds\Core\Config;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Di\Di;
use Minds\Core\Hashtags\HashtagEntity;
use Minds\Core\Hashtags\Trending\Repository as TrendingRepository;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Entities\User;

class Manager
{
    /** @var User */
    private $user;

    /** @var Repository */
    private $repository;

    /** @var TrendingRepository */
    private $trendingRepository;

    /** @var abstractCacher */
    private $cacher;

    /** @var Config */
    private $config;

    /** @var PseudoHashtags */
    private $pseudoHashtags;

    public function __construct(
        $repository = null,
        $trendingRepository = null,
        $cacher = null,
        $config = null,
        PseudoHashtags $pseudoHashtags = null,
        private ?ExperimentsManager $experimentsManager = null
    ) {
        $this->repository = $repository ?: new Repository;
        $this->trendingRepository = $trendingRepository ?: new TrendingRepository;
        $this->cacher = $cacher ?: Di::_()->get('Cache');
        $this->config = $config ?: Di::_()->get('Config');
        $this->pseudoHashtags = $pseudoHashtags ?? new PseudoHashtags();
        $this->experimentsManager ??= Di::_()->get('Experiments\Manager');
    }

    /**
     * Set the user
     * @param User $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Return user hashtags alongside some suggestions
     * @param array $opts
     * @return array
     * @throws \Exception
     */
    public function get($opts)
    {
        $opts = array_merge([
            'limit' => 10,
            'trending' => false,
            'defaults' => true,
            'user_guid' => $this->user ? $this->user->getGuid() : null,
            'languages' => [ 'en' ],
            'wire_support_tier' => null
        ], $opts); // Merge in our defaults

        if ($this->user && $this->user->getLanguage() !== 'en') {
            $opts['languages'] = [ 'en', $this->user->getLanguage() ];
        }

        // User hashtags

        $selected = [];

        if ($this->user) {
            $cached =  $this->cacher->get($this->getCacheKey());

            if ($cached !== false) {
                $selected = json_decode($cached, true);
            } else {
                $response = $this->repository->getAll($opts);

                if ($response) {
                    $selected = $response->map(function ($row) {
                        return $row->toArray();
                    })->toArray();

                    $this->cacher->set($this->getCacheKey(), json_encode($selected), 7 * 24 * 60 * 60); // 1 week (busted on changes)

                    $this->pseudoHashtags->syncTags($response->toArray());
                }
            }
        }

        // Trending hashtags

        $trending = [];

        if ($opts['trending']) {
            $cached = $this->cacher->get($this->getSharedCacheKey('trending', $opts));
            // $cached = false;

            if ($cached !== false) {
                $trending = json_decode($cached, true);
            } else {
                $results = $this->trendingRepository->getList($opts);

                if ($results) {
                    $trending = $results;
                    $this->cacher->set($this->getSharedCacheKey('trending', $opts), json_encode($trending), 60 * 15); // 15 minutes
                }
            }
        }

        // Default hashtags
        $defaults = [];

        if ($opts['defaults']) {
            $v2Tags = $this->config->get('tags_v2') ?? false;
            $defaults = $this->isDefaultTagsV2ExperimentActive() && $v2Tags
                ? $v2Tags
                : $this->config->get('tags');
        }

        // Merge and output

        $output = [];

        foreach ($selected as $row) {
            $tag = $row['hashtag'];

            $output[$tag] = [
                'selected' => true,
                'value' => $tag,
                'type' => 'user',
            ];
        }

        foreach ($trending as $tag) {
            $posts = $tag['posts'];
            $votes = $tag['votes'];
            $tag = $tag['tag'];
            if (isset($output[$tag])) {
                continue;
            }

            $output[$tag] = [
                'selected' => false,
                'value' => $tag,
                'posts_count' => $posts,
                'votes_count' => $votes,
                'type' => 'trending',
            ];
        }

        foreach ($defaults as $tag) {
            if (isset($output[$tag])) {
                continue;
            }

            $output[$tag] = [
                'selected' => false,
                'value' => $tag,
                'type' => 'default',
            ];
        }

        return array_slice(array_values($output), 0, count($selected) + $opts['limit']);
    }

    /**
     * @param HashtagEntity[] $hashtags
     * @return bool
     */
    public function add(array $hashtags)
    {
        $success = $this->repository->add($hashtags);

        if ($success) {
            $this->cacher->destroy($this->getCacheKey());

            $this->pseudoHashtags->addTags($hashtags);
        }

        return $success;
    }

    /**
     * @param HashtagEntity[] $add
     * @param HashtagEntity[] $remove
     * @return bool
     */
    public function batch(array $add, array $remove): bool
    {
        $success = $this->repository->batch($add, $remove);

        if ($success) {
            $this->cacher->destroy($this->getCacheKey());

            $this->pseudoHashtags->addTags($add);
            $this->pseudoHashtags->removeTags($remove);
        }

        return $success;
    }

    /**
     * @param HashtagEntity[] $hashtags
     * @return bool
     */
    public function remove(array $hashtags)
    {
        $success = $this->repository->remove($hashtags);

        if ($success) {
            $this->cacher->destroy($this->getCacheKey());

            $this->pseudoHashtags->removeTags($hashtags);
        }

        return $success;
    }

    /**
     * @return string
     */
    public function getCacheKey($extra = '')
    {
        return "hashtags::user-selected::{$this->user->getGuid()}" . ($extra ? ":{$extra}" : '');
    }

    /**
     * @return string
     */
    public function getSharedCacheKey($key, $opts): string
    {
        $languages = implode(':', $opts['languages']);
        return "hashtags::shared::$key::$languages";
    }

    /**
     * Whether a user has set hashtags.
     * @param User $user - user to check for.
     * @return boolean - true if user has set hashtags.
     */
    public function hasSetHashtags(): bool
    {
        $userHashtags = $this->get(['limit' => 1]);
        
        return $userHashtags &&
            count($userHashtags) > 0 &&
            $userHashtags[0]['selected'];
    }
    
    /**
     * Whether default tags v2 experiment is active.
     * @return bool true if default tags v2 experiment is active.
     */
    private function isDefaultTagsV2ExperimentActive(): bool
    {
        return $this->experimentsManager
            ->setUser($this->user)
            ->isOn('minds-3216-default-tags-v2');
    }
}
