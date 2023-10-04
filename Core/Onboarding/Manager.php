<?php

namespace Minds\Core\Onboarding;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
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

    /** @var Config */
    protected $config;

    /** @var User */
    protected $user;

    /** @var OnboardingGroups\InitialOnboardingGroup */
    protected $initialOnboarding;

    /** @var OnboardingGroups\OnogingOnboardingGroup */
    protected $ongoingOnboarding;

    protected Save $save;

    /**
     * Manager constructor.
     *
     * @param Steps\OnboardingStepInterface[] $steps
     * @param Config $config
     */
    public function __construct(
        $steps = null,
        $config = null,
        $initialOnboarding = null,
        $ongoingOnboarding = null,
        $save = null,
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->initialOnboarding = $initialOnboarding ?? new OnboardingGroups\InitialOnboardingGroup();
        $this->ongoingOnboarding = $ongoingOnboarding ?? new OnboardingGroups\OngoingOnboardingGroup();
        $this->save ??= new Save();

        if ($steps) {
            $this->steps = $steps;
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

        $this->user
            ->setOnboardingShown($onboardingShown);

        $saved = $this->save->setEntity($this->user)
            ->withMutatedAttributes([
                'onboarding_shown',
            ])
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

        $this->user
            ->setInitialOnboardingCompleted(time());

        $saved = $this->save->setEntity($this->user)
            ->withMutatedAttributes([
                'initial_onboarding_completed',
            ])
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

        $this->user
            ->setCreatorFrequency($creatorFrequency);

        $saved = $this->save->setEntity($this->user)
            ->withMutatedAttributes([
                'creator_frequency',
            ])
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
     * Returns the onboarding timestamp
     * @return int
     */
    private function getOnboardingFeatureTimestamp(): int
    {
        return $this->config->get('onboarding_v2_timestamp') ?: 0;
    }
}
