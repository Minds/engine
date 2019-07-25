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

    public function export(): array
    {
        return [
            'user_guid' => $this->userGuid,
            'reference_date' => $this->referenceDateMs,
            'state' => $this->state,
            'previous_state' => $this->previousState,
            'activity_percentage' => $this->activityPercentage,
            'reward_factor' => RewardFactor::getForUserState($this->state),
            'previous_reward_factor' => RewardFactor::getForUserState($this->previousState),
        ];
    }

    public static function fromArray(array $data): UserState {
        return (new UserState())
            ->setUserGuid($data['user_guid'])
            ->setReferenceDateMs($data['reference_date'])
            ->setState($data['state'])
            ->setPreviousState($data['previous_state'])
            ->setActivityPercentage($data['activity_percentage']);
    }
}
