<?php
namespace Minds\Core\Onboarding\OnboardingGroups;

use Minds\Core\Onboarding\Steps\OnboardingStepInterface;
use Minds\Entities\User;

abstract class AbstractOnboardingGroup implements OnboardingGroupInterface
{
    /** @var User */
    protected $user;

    /** @var OnboardingStepInterface[] */
    protected $steps = [];

    /**
     * Sets the user we are interfacing with
     * @param User $user
     * @return OnboardingGroupInterface
     */
    public function setUser(User $user): OnboardingGroupInterface
    {
        $group = clone $this;
        $group->user = $user;
        return $group;
    }

    /**
     * Returns a shortId
     * @return string
     */
    public function getId(): string
    {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }

    /**
     * Returns the steps for the onboarding groups
     * @return OnboardingStepInterface[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Returns a percentage of completed steps
     * @return float
     */
    public function getCompletedPct(): float
    {
        $stepsCount = count($this->getSteps());
        $stepsCompleted = count(array_filter($this->getSteps(), function ($step) {
            return $step->isCompleted($this->user);
        }));

        return $stepsCompleted / $stepsCount;
    }

    /**
     * Returns if completed.
     * NOTE: 100% completed progress does not confirm this is completed, eg.
     * Initial completion is a user defined field, as we may change the steps.
     * Ongoing onboarding, however,  will never be fully completed as we keep adding steps
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->getCompletedPct() === (float) 1;
    }

    /**
     * Export the publically viewable model
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'id' => $this->getId(),
            'completed_pct' => $this->getCompletedPct(),
            'is_completed' => $this->isCompleted(),
            'steps' => array_map(function ($step) {
                // TODO -> implement export for steps interface
                // return $step->export();

                return [
                    'id' => (new \ReflectionClass($step))->getShortName(),
                    'is_completed' => $step->isCompleted($this->user)
                ];
            }, $this->getSteps()),
        ];
    }
}
