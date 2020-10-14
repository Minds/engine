<?php
namespace Minds\Core\Onboarding\OnboardingGroups;

use Minds\Core\Onboarding\Steps\OnboardingStepInterface;
use Minds\Core\Onboarding\Steps;

class OngoingOnboardingGroup extends AbstractOnboardingGroup
{
    public function __construct($steps = null)
    {
        $this->steps = $steps ?? [
            new Steps\SuggestedChannelsStep(),
            // Download mobile app - TODO
            new Steps\SuggestedGroupsStep(),
            // Upgrade to Minds+
            // Refer a friend
            // Setup wallet
            // Setup membership toers
        ];
    }
}
