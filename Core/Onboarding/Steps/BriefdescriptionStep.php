<?php
/**
 * Briefdescription.
 *
 * @author emi
 */

namespace Minds\Core\Onboarding\Steps;

use Minds\Entities\User;

class BriefdescriptionStep implements OnboardingStepInterface
{
    /**
     * @param User $user
     * @return bool
     */
    public function isCompleted(User $user)
    {
        return (bool) $user->briefdescription;
    }
}
