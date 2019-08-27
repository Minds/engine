<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Pro\Channel;

use Exception;
use Minds\Core\Feeds\Top\Manager as TopManager;
use Minds\Core\Pro\Repository;
use Minds\Core\Pro\Settings;
use Minds\Entities\User;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var TopManager */
    protected $top;

    /** @var User */
    protected $user;

    /**
     * Manager constructor.
     * @param Repository $repository
     * @param TopManager $top
     */
    public function __construct(
        $repository = null,
        $top = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->top = $top ?: new TopManager();
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAllCategoriesContent()
    {
        if (!$this->user) {
            throw new Exception('No user set');
        }

        /** @var Settings $settings */
        $settings = $this->repository->getList([
            'user_guid' => $this->user->guid
        ])->first();

        if (!$settings) {
            throw new Exception('Invalid Pro user');
        }

        $tags = $settings->getTagList() ?: [];

        $output = [];

        $container = (string) $this->user->guid;

        foreach ($tags as $tag) {
            $opts = [
                'container_guid' => $container,
                'access_id' => [2, $container],
                'hashtags' => [strtolower($tag['tag'])],
                'filter_hashtags' => true,
                'limit' => 4,
                'type' => 'all',
                'algorithm' => 'top',
                'period' => '7d',
                'sync' => true,
                'single_owner_threshold' => 0,
            ];

            $content = $this->top->getList($opts)->toArray();

            if (count($content) < 2) {
                $opts['algorithm'] = 'latest';
                $content = $this->top->getList($opts)->toArray();
            }

            $output[] = [
                'tag' => $tag,
                'content' => $content,
            ];
        }

        return $output;
    }
}
