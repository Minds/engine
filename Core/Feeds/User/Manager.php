<?php

namespace Minds\Core\Feeds\User;

use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Entities\User;

/**
 * Manager handling functions for a feed related to a specific user.
 */
class Manager
{
    // instance user.
    private ?User $user;

    /**
     * Constructor.
     * @param ElasticManager|null $feedManager - feed manager.
     */
    public function __construct(
        private ?ElasticManager $feedManager = null
    ) {
        $this->feedManager ??= Di::_()->get('Feeds\Elastic\Manager');
    }

    /**
     * Set instance user.
     * @param User $user - user to set.
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Whether user has made at least one post.
     * @param string $period - period to check, defaults to 1y.
     * @throws \Exception - on error.
     * @return bool true if user has made at least one post.
     */
    public function hasMadePosts(string $period = '1y'): bool
    {
        $opts = [
            'container_guid' => $this->user->getGuid(),
            'algorithm' => 'latest',
            'period' => $period,
            'type' => 'activity'
        ];

        return $this->feedManager->getCount(
            opts: $opts,
            handleExceptions: false
        ) > 0;
    }
}
