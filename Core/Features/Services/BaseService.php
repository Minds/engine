<?php
/**
 * BaseService
 *
 * @author edgebal
 */

namespace Minds\Core\Features\Services;

use Minds\Entities\User;

/**
 * Base service to be used when building integrations
 * @package Minds\Core\Features\Services
 */
abstract class BaseService implements ServiceInterface
{
    /** @var User */
    protected $user;

    /**
     * @inheritDoc
     * @param User|null $user
     * @return ServiceInterface
     */
    public function setUser(?User $user): ServiceInterface
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Calculate user groups based on their state
     * @return array
     */
    public function getUserGroups(): array
    {
        $groups = [];

        if (!$this->user) {
            $groups[] = 'anonymous';
        } else {
            $groups[] = 'authenticated';

            if ($this->user->isAdmin()) {
                $groups[] = 'admin';
            }

            if ($this->user->isCanary()) {
                $groups[] = 'canary';
            }

            if ($this->user->isPro()) {
                $groups[] = 'pro';
            }

            if ($this->user->isPlus()) {
                $groups[] = 'plus';
            }
        }

        return $groups;
    }
}
