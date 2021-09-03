<?php
/**
 * Verify Email.
 *
 * @author Mark Harding
 */

namespace Minds\Core\Onboarding\Steps;

use Minds\Entities\User;

class VerifyEmailStep implements OnboardingStepInterface
{
    /**
     * @param User $user
     * @return bool
     */
    public function isCompleted(User $user)
    {
        return $user->isTrusted();
    }
}
