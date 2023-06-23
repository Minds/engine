<?php
declare(strict_types=1);

namespace Minds\Core\Onboarding\V5\GraphQL\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Onboarding\V5\Manager;
use Minds\Core\Onboarding\V5\GraphQL\Types\OnboardingState;
use Minds\Core\Onboarding\V5\GraphQL\Types\OnboardingStepProgressState;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use Minds\Core\GraphQL\Types\KeyValuePair;

/**
 * GraphQL Controller for Onboarding V5.
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null,
    ) {
        $this->manager ??= Di::_()->get(Manager::class);
    }

    /**
     * Gets onboarding state for the currently logged in user.
     * @return ?OnboardingState current onboarding state.
     */
    #[Query]
    #[Logged]
    public function getOnboardingState(#[InjectUser] User $loggedInUser): ?OnboardingState
    {
        return $this->manager->getOnboardingState($loggedInUser);
    }

    /**
     * Sets onboarding state for the currently logged in user.
     * @param bool $completed - whether onboarding is to be set to completed or now.
     * @return OnboardingState current onboarding state.
     */
    #[Mutation]
    #[Logged]
    public function setOnboardingState(
        bool $completed,
        #[InjectUser] User $loggedInUser
    ): OnboardingState {
        return $this->manager->setOnboardingState($loggedInUser, $completed);
    }

    /**
     * Get the currently logged in users onboarding step progress.
     * @return OnboardingStepProgressState[] onboarding step process.
     */
    #[Query]
    #[Logged]
    public function getOnboardingStepProgress(#[InjectUser] User $loggedInUser): array
    {
        return $this->manager->getOnboardingStepProgress($loggedInUser);
    }

    /**
     * Mark an onboarding step for a user as completed.
     * @param string $stepKey - key of the current step, should be unique.
     * @param string $stepType - type of step.
     * @param ?KeyValuePair[] $additionalData - additional data for processing.
     * @return OnboardingStepProgressState - updated progress state.
     */
    #[Mutation]
    #[Logged]
    public function completeOnboardingStep(
        #[InjectUser] User $loggedInUser,
        string $stepKey,
        string $stepType,
        ?array $additionalData = null
    ): OnboardingStepProgressState {
        return $this->manager->completeOnboardingStep(
            user: $loggedInUser,
            stepKey: $stepKey,
            stepType: $stepType,
            additionalData: $additionalData
        );
    }
}
