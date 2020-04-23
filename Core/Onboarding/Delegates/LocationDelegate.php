<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Onboarding\Delegates;

use Minds\Entities\User;

class LocationDelegate implements OnboardingDelegate
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
