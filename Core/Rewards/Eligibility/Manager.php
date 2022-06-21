<?php

namespace Minds\Core\Rewards\Eligibility;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Core\Feeds\User\Manager as FeedsUserManager;

/**
 * Manager with the responsibility of determining a members
 * eligibility for rewards.
 */
class Manager
{
    // Minimum account age to be able to register for rewards.
    const MINIMUM_ACCOUNT_AGE = 259200;

    // Instance of user.
    private ?User $user;

    /**
     * Constructor.
     * @param ?UserHashtagsManager $userHashtagsManager.
     * @param ?FeedsUserManager $feedUserManager.
     */
    public function __construct(
        private ?UserHashtagsManager $userHashtagsManager = null,
        private ?FeedsUserManager $feedUserManager = null
    ) {
        $this->userHashtagsManager ??= new UserHashtagsManager();
        $this->feedUserManager ??= Di::_()->get('Feeds\User\Manager');
    }

    /**
     * Setter for instance $user.
     * @param User $user - user to set instance $user to.
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Whether instance $user can register.
     * @throws ServerErrorException - if no user is set.
     * @return boolean - true if user can register.
     */
    public function isEligible(): bool
    {
        if (!$this->user) {
            throw new ServerErrorException('No user set for Eligibility Manager');
        }

        return $this->user->isTrusted() &&
            $this->user->getAge() > self::MINIMUM_ACCOUNT_AGE &&
            $this->user->getName() &&
            $this->user->briefdescription &&
            $this->hasMadePosts() &&
            $this->hasSetHashtags();
    }

    /**
     * Whether user has set hashtags.
     * @param User $user - user to check for.
     * @return boolean - true if user has set hashtags.
     */
    private function hasSetHashtags(): bool
    {
        return $this->userHashtagsManager->setUser($this->user)
            ->hasSetHashtags();
    }

    /**
     * Whether user has made a single post.
     * @param User $user - user to check for.
     * @return boolean - true if user has made a single post.
     */
    private function hasMadePosts(): bool
    {
        return $this->feedUserManager->setUser($this->user)
            ->hasMadePosts();
    }
}
