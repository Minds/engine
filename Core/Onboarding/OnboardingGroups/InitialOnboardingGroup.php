<?php
namespace Minds\Core\Onboarding\OnboardingGroups;

use Minds\Core\Onboarding\Steps;
use Minds\Core\Onboarding\Steps\OnboardingStepInterface;

class InitialOnboardingGroup extends AbstractOnboardingGroup
{
    /** @var OnboardingStepInterface[] */
    protected $steps;

    public function __construct($steps = null)
    {
        $this->steps = $steps ?? [
            new Steps\VerifyEmailStep(),
            new Steps\SuggestedHashtagsStep(),
            new Steps\SetupChannelStep(),
            new Steps\VerifyUniquenessStep(),
            // Set content preferences - TODO
            new Steps\CreatePostStep(),
            // new Steps\InitialOnboardingRewardStep(),
        ];
    }

    /**
     * Returns if completed.
     * NOTE: 100% completed progress does not confirm this is completed, eg.
     * Initial completion is a user defined field, as we may change the steps.
     * Ongoing onboarding, however,  will never be fully completed as we keep adding steps
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->user->getInitialOnboardingCompleted() > 0;
    }
}
