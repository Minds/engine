<?php
/**
 * SuggestedChannels.
 *
 * @author emi
 */

namespace Minds\Core\Onboarding\Steps;

use Minds\Entities\User;

class SuggestedChannelsStep implements OnboardingStepInterface
{
    /**
     * @param User $user
     * @return bool
     */
    public function isCompleted(User $user)
    {
        return $user->getSubscriptonsCount() > 1; // Channels are always subscribed to @minds
    }
}
