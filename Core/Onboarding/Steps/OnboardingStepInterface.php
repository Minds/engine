<?php

namespace Minds\Core\Onboarding\Steps;

use Minds\Entities\User;

/**
 * OnboardingDelegate
 *
 * @author edgebal
 */

interface OnboardingStepInterface
{
    /**
     * @param User $user
     * @return bool
     */
    public function isCompleted(User $user);
}
