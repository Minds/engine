<?php
declare(strict_types=1);

namespace Minds\Core\Onboarding\V5;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Onboarding\V5\Repository;
use Minds\Core\Onboarding\V5\GraphQL\Types\OnboardingState;
use Minds\Core\Onboarding\V5\GraphQL\Types\OnboardingStepProgressState;
use Minds\Entities\User;

/**
 * Onboarding V5 manager - manages user and step completion states.
 */
class Manager
{
    public function __construct(
        private ?Repository $repository = null,
        private ?Save $save = null
    ) {
        $this->repository ??= Di::_()->get(Repository::class);
        $this->save ??= new Save();
    }

    /**
     * Gets onboarding state for a given user.
     * @param User $user - user to get state for.
     * @return ?OnboardingState onboarding state.
     */
    public function getOnboardingState(User $user): ?OnboardingState
    {
        return $this->repository->getOnboardingState((int) $user->getGuid());
    }

    /**
     * Sets onboarding state for a given user.
     * @param User $user - user to set state for.
     * @param bool $completed - whether to set onboarding to completed or not.
     * @return OnboardingState onboarding state.
     */
    public function setOnboardingState(User $user, bool $completed): OnboardingState
    {
        return $this->repository->setOnboardingState(
            (int) $user->getGuid(),
            $completed
        );
    }

    /**
     * Get a given users onboarding step progress.
     * @param User $user - user to get state for.
     * @return OnboardingStepProgressState[] array of onboarding steps a user has saved progress for.
     */
    public function getOnboardingStepProgress(User $user): array
    {
        return iterator_to_array(
            $this->repository->getOnboardingStepProgress((int) $user->getGuid())
        );
    }

    /**
     * Get a given users onboarding step progress.
     * @param User $user - user to set state for.
     * @param string $stepKey - key of the current step, should be unique.
     * @param string $stepType - type of step.
     * @param ?KeyValuePair[] $additionalData - additional data for processing.
     * @return OnboardingStepProgressState array of onboarding steps a user has saved progress for.
     */
    public function completeOnboardingStep(
        User $user,
        string $stepKey,
        string $stepType,
        ?array $additionalData = []
    ): OnboardingStepProgressState {
        $this->handleAdditionalData($user, $stepKey, $additionalData);

        return $this->repository->completeOnboardingStep(
            (int) $user->getGuid(),
            $stepKey,
            $stepType
        );
    }

    /**
     * Handles the processing of any additional passed data that may be optionally passed.
     * @param User $user - user to process data for.
     * @param ?KeyValuePair[] $additionalData - additional data to process.
     * @return void
     */
    private function handleAdditionalData(User $user, string $stepKey, array $additionalData = []): void
    {
        foreach ($additionalData as $data) {
            if ($stepKey === 'onboarding_interest_survey' && $data->key === 'onboarding_interest' && $data->value) {
                $user->setOnboardingInterest($data->value);
                $this->save
                    ->setEntity($user)
                    ->withMutatedAttributes(['onboarding_interest'])
                    ->save();
            }
        }
    }
}
