<?php
namespace Minds\Core\Onboarding\OnboardingGroups;

use Minds\Entities\User;
use Minds\Core\Onboarding\Steps\OnboardingStepInterface;

interface OnboardingGroupInterface
{
    /**
     * Sets the user we are interfacing with
     * @param User $user
     * @return OnboardingGroupInterface
     */
    public function setUser(User $user): OnboardingGroupInterface;

    /**
     * Returns the steps for the onboarding groups
     * @return OnboardingStepInterface[]
     */
    public function getSteps(): array;

    /**
     * Returns a percentage of completed steps
     * @return float
     */
    public function getCompletedPct(): float;

    /**
     * Returns if completed.
     * NOTE: 100% completed progress does not confirm this is completed, eg.
     * Initial completion is a user defined field, as we may change the steps.
     * Ongoing onboarding, however,  will never be fully completed as we keep adding steps
     * @return bool
     */
    public function isCompleted(): bool;

    /**
     * Export the publically viewable model
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array;
}
