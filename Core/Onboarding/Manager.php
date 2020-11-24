<?php

namespace Minds\Core\Onboarding;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Features\Exceptions\FeatureNotImplementedException;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Entities\User;

class Manager
{
    /** @var array */
    const CREATOR_FREQUENCIES = [
        'rarely',
        'sometimes',
        'frequently',
    ];

    /** @var Steps\OnboardingStepInterface[] */
    protected $steps;

    /** @var FeaturesManager */
    protected $features;

    /** @var Config */
    protected $config;

    /** @var User */
    protected $user;

    /** @var OnboardingGroups\InitialOnboardingGroup */
    protected $initialOnboarding;

    /** @var OnboardingGroups\OnogingOnboardingGroup */
    protected $ongoingOnboarding;

    /**
     * Manager constructor.
     *
     * @param Steps\OnboardingStepInterface[] $steps
     * @param FeaturesManager $features
     * @param Config $config
     * @throws FeatureNotImplementedException
     */
    public function __construct(
        $steps = null,
        $features = null,
        $config = null,
        $initialOnboarding = null,
        $ongoingOnboarding = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->features = $features ?: Di::_()->get('Features\Manager');
        $this->initialOnboarding = $initialOnboarding ?? new OnboardingGroups\InitialOnboardingGroup();
        $this->ongoingOnboarding = $ongoingOnboarding ?? new OnboardingGroups\OngoingOnboardingGroup();

        if ($steps) {
            $this->steps = $steps;
        //} elseif ($this->features->has('onboarding-october-2020')) {
            // October 2020 - see getOnboardingGroup
        } else {
            $this->steps = [
                'suggested_hashtags' => new Steps\SuggestedHashtagsStep(),
                'tokens_verification' => new Steps\TokensVerificationStep(),
                'location' => new Steps\LocationStep(),
                'dob' => new Steps\DateOfBirthStep(),
                'avatar' => new Steps\AvatarStep(),
            ];
        }
    }

    /**
     * @param User $user
     *
     * @return Manager
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function wasOnboardingShown()
    {
        if (!$this->user) {
            throw new \Exception('User not set');
        }

        $timestamp = $this->getOnboardingFeatureTimestamp();

        return $this->user->getTimeCreated() <= $timestamp || $this->user->wasOnboardingShown();
    }

    /**
     * @param bool $onboardingShown
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function setOnboardingShown($onboardingShown)
    {
        if (!$this->user) {
            throw new \Exception('User not set');
        }

        $saved = $this->user
            ->setOnboardingShown($onboardingShown)
            ->save();

        return (bool) $saved;
    }

    /**
     * @param int $ts
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function setInitialOnboardingComplete($ts)
    {
        if (!$this->user) {
            throw new \Exception('User not set');
        }

        $saved = $this->user
            ->setInitialOnboardingCompleted(time())
            ->save();

        return (bool) $saved;
    }

    /**
     * Returns the currently valid onboarding group
     * @return OnboardingGroups\OnboardingGroupInterface
     */
    public function getOnboardingGroup(): OnboardingGroups\OnboardingGroupInterface
    {
        // MH TODO: For existing users, do we now just skip the initial onboarding?

        $initialOnboarding = $this->initialOnboarding
            ->setUser($this->user);

        if (!$initialOnboarding->isCompleted()) {
            // Initial onboarding is slightly different to other onboarding groups
            // Here, the onboarding is completed when the user has a getInitialOnboardingComplete()
            // value returned. We set this at the time $initialOnboarding->getCompletedPct() returns 100%
            // - this allows us to change the initial onboarding and not disrupt existing users progress

            if ($initialOnboarding->getCompletedPct() < 1) {
                return $initialOnboarding;
            }

            // Onboarding is actually completed, so lets now apply this tag to the user
            // so we don't ask them to do initial onboarding again
            
            $this->setInitialOnboardingComplete(time());

            // TODO: Implement a delegate to notify user of reward to claim
        }

        // Everybody else get the ongoing onboarding

        $ongoingOnboarding = $this->ongoingOnboarding
            ->setUser($this->user);
        return $ongoingOnboarding;
    }

    // LEGACY ONBOARDING... REMOVE ONCE FEATURE FLAG IS OUT (OCT 2020 - MH)

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function getCreatorFrequency()
    {
        if (!$this->user) {
            throw new \Exception('User not set');
        }

        return $this->user->getCreatorFrequency();
    }

    /**
     * @param string $creatorFrequency
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function setCreatorFrequency($creatorFrequency)
    {
        if (!$this->user) {
            throw new \Exception('User not set');
        }

        if (!in_array($creatorFrequency, static::CREATOR_FREQUENCIES, true)) {
            throw new \Exception('Invalid creator frequency');
        }

        $saved = $this->user
            ->setCreatorFrequency($creatorFrequency)
            ->save();

        return (bool) $saved;
    }

    /**
     * @return string[]
     */
    public function getAllItems()
    {
        return array_keys($this->steps);
    }

    /**
     * @return string[]
     *
     * @throws \Exception
     */
    public function getCompletedItems()
    {
        if (!$this->user) {
            throw new \Exception('User not set');
        }

        $completedSteps = [];

        foreach ($this->steps as $k => $step) {
            /** @var Steps\OnboardingDelegate $delegate */
            if ($step->isCompleted($this->user)) {
                $completedSteps[] = $k;
            }
        }

        return $completedSteps;
    }

    /**
     * Compares a user's list of completed items against the number of registered onboarding steps.
     *
     * @return bool
     */
    public function isComplete()
    {
        return count($this->getAllItems()) === count($this->getCompletedItems());
    }

    /**
     * Returns the currently enabled onboarding feature timestamp
     * @return int
     * @throws FeatureNotImplementedException
     */
    private function getOnboardingFeatureTimestamp(): int
    {
        $key = $this->features->has('ux-2020') ? 'onboarding_v2_timestamp' : 'onboarding_modal_timestamp';
        return $this->config->get($key) ?: 0;
    }
}
