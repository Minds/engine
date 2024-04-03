<?php

/**
 * Experiments Manager. Handles experiments and feature flags
 * specified within Growthbook. State of a flag can be checked by calling
 * setUser($user) followed by isOn($flag). This will check both experiments
 * AND features. Also for checking experiments you can use the
 * hasVariation(key, val) function.
 */
namespace Minds\Core\Experiments;

use Minds\Entities\User;
use Growthbook;
use GuzzleHttp;
use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Experiments\Cookie\Manager as CookieManager;
use Minds\Core\Data\cache\SharedCache;
use Minds\Core\Data\cache\WorkerCache;

class Manager
{
    /** @var User */
    private $user;

    public function __construct(
        private ?PostHogService $postHogService = null
    ) {
        $this->postHogService = $postHogService ?? Di::_()->get(PostHogService::class);
    }

    /**
     * Getter for class level user.
     * @return User|null - User that has been set.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the user who will view experiments
     * @param User $user (optional)
     * @return Manager
     */
    public function setUser(User $user = null): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Whether feature / experiment is on or off.
     * @param string $featureKey - the key of the feature.
     * @return boolean - true if feature / experiment is on.
     */
    public function isOn(string $featureKey): bool
    {
        return !!($this->postHogService->withUser($this->user)->getFeatureFlags()[$featureKey] ?? false);
    }

    /**
     * Whether user has been put in the specified variation of an experiment.
     * @param string $featureKey - the key of the feature.
     * @param $variationId - the variation label.
     * @return boolean - true if feature is on and experiment is active for user.
     */
    public function hasVariation(string $featureKey, $variation): bool
    {
        $value = $this->postHogService->withUser($this->user)->getFeatureFlags()[$featureKey] ?? null;
        return $value === $variation;
    }

}
