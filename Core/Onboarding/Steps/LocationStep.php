<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Onboarding\Steps;

use Minds\Entities\User;

class LocationStep implements OnboardingStepInterface
{
    /**
     * @param User $user
     * @return bool
     */
    public function isCompleted(User $user)
    {
        return (bool) $user->city;
    }
}
