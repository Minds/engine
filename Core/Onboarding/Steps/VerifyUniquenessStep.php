<?php
/**
 * VerifyUniquenessStep
 *
 * @author Mark Harding
 */

namespace Minds\Core\Onboarding\Steps;

use Minds\Entities\User;

class VerifyUniquenessStep implements OnboardingStepInterface
{
    /**
     * @param User $user
     * @return bool
     */
    public function isCompleted(User $user)
    {
        $tokenVerificationStep = new TokensVerificationStep();
        return $tokenVerificationStep->isCompleted($user);
    }
}
