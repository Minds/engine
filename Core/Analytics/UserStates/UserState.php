<?php
/**
 * User states.
 */

namespace Minds\Core\Analytics\UserStates;

use Minds\Traits\MagicAttributes;

/**
 * Class UserState
 * @package Minds\Core\Analytics\UserStates
 *
 * @method UserState setUserGuid(int $userGuid)
 * @method int getUserGuid()
 * @method UserState setReferenceDateMs(int $referenceDateMs)
 * @method int getReferenceDateMs()
 * @method UserState setState(string $state)
 * @method string getState()
 * @method UserState setPreviousState(string $state)
 * @method string getPreviousState()
 * @method UserState setActivityPercentage(float $activityPercentage)
 * @method float getActivityPercentage()
 * @method UserState setStateChange(int $stateChange)
 * @method int getStateChange()
 */
class UserState
{
    const STATE_CASUAL = 'casual';
    const STATE_COLD = 'cold';
    const STATE_CORE = 'core';
    const STATE_CURIOUS = 'curious';
    const STATE_NEW = 'new';
    const STATE_RESURRECTED = 'resurrected';
    const STATE_UNKNOWN = 'unknown';

    const STATE_INDEXES = [
        self::STATE_UNKNOWN => 0,
        self::STATE_NEW => 1,
        self::STATE_COLD => 2,
        self::STATE_RESURRECTED => 3,
        self::STATE_CURIOUS => 4,
        self::STATE_CASUAL => 5,
        self::STATE_CORE => 6,
    ];

    use MagicAttributes;

    /** @var int $userGuid */
    private $userGuid;

    /** @var int $referenceDateMs */
    private $referenceDateMs;

    /** @var string $state */
    private $state;

    /** @var string $previousState */
    private $previousState;

    /** @var float $activityPercentage */
    private $activityPercentage;

    /** @var int $stateChange */
    private $stateChange;

    public function export(): array
    {
        $this->deriveStateChange();
        return [
            'user_guid' => $this->userGuid,
            'reference_date' => $this->referenceDateMs,
            'state' => $this->state,
            'previous_state' => $this->previousState,
            'activity_percentage' => $this->activityPercentage,
            'reward_factor' => RewardFactor::getForUserState($this->state),
            'previous_reward_factor' => RewardFactor::getForUserState($this->previousState),
            'state_change' => $this->stateChange
        ];
    }

    private function deriveStateChange(): void
    {
        $this->stateChange = self::stateChange($this->previousState, $this->state);
    }

    public static function stateChange(?string $oldState, ?string $newState): int
    {
        $oldStateIndex = self::STATE_INDEXES[$oldState] ?? 0;
        $newStateIndex = self::STATE_INDEXES[$newState] ?? 0;

        return $newStateIndex - $oldStateIndex;
    }

    public static function fromArray(array $data): UserState
    {
        return (new UserState())
            ->setUserGuid($data['user_guid'])
            ->setReferenceDateMs($data['reference_date'])
            ->setState($data['state'])
            ->setPreviousState($data['previous_state'])
            ->setActivityPercentage($data['activity_percentage'])
            ->setStateChange(self::stateChange($data['previous_state'], $data['state']));
    }
}
