<?php

namespace Minds\Core\Analytics\Delegates;

use Minds\Core\Analytics\UserStates\UserState;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Notification\Notification;
use Minds\Entities\User;

class UpdateUserStateEstimate
{
    /** @var User */
    private $user;
    /** @var UserState */
    private $userState;
    /** @var int */
    private $estimateStateChange;

    public function __construct(UserState $userState, User $user = null)
    {
        $this->userState = $userState;
        $this->user = $user ?? new User($userState->getUserGuid());
        $this->estimateStateChange = 0;
    }

    public function update(): void
    {
        $this->deriveEstimateChange();
        $this->updateUserEntity();
        $this->sendStateChangeNotification();
    }

    private function deriveEstimateChange(): void
    {
        $this->estimateStateChange = UserState::stateChange($this->user->getUserStateToday(), $this->userState->getState());
    }

    private function updateUserEntity(): void
    {
        $this->user->setUserStateToday($this->userState->getState())
            ->setUserStateTodayUpdatedMs(time() * 1000)
            ->save();
    }

    private function sendStateChangeNotification(): void
    {
        $ignoreStates = [
            UserState::STATE_UNKNOWN,
            UserState::STATE_RESURRECTED,
            UserState::STATE_NEW
        ];

        if ($this->estimateStateChange < 0 &&
            !in_array($this->userState->getState(), $ignoreStates, false)) {
            $notificationView = 'rewards_state_decrease_today';
            Dispatcher::trigger('notification', 'reward', [
                'to' => [
                    $this->userState->getUserGuid()
                ],
                'from' => Notification::SYSTEM_ENTITY,
                'notification_view' => $notificationView,
                'params' => $this->userState->export()
            ]);
        }
    }
}
