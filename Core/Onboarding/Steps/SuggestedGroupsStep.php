<?php
/**
 * SuggestedGroups.
 *
 * @author emi
 */

namespace Minds\Core\Onboarding\Steps;

use Minds\Entities\User;

class SuggestedGroupsStep implements OnboardingStepInterface
{
    /**
     * @param User $user
     * @return bool
     */
    public function isCompleted(User $user)
    {
        return count($user->getGroupMembership() ?: []) > 0;
    }
}
