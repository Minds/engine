<?php
namespace Minds\Core\Onboarding\OnboardingGroups;

use Minds\Core\Onboarding\Steps\OnboardingStepInterface;

class OngoingOnboardingGroup extends AbstractOnboardingGroup
{
    public function __construct($steps = [])
    {
        $this->steps = $steps ?? [
            new SuggestedChannelsStep(),
            // Download mobile app - TODO
            new SuggestedGroupsStep(),
            // Upgrade to Minds+
            // Refer a friend
            // Setup wallet
            // Setup membership toers
        ];
    }
}
