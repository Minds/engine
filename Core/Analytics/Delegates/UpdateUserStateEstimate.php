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
            ->setUserStateTodayUpdatedMs($this->userState->getReferenceDateMs())
            ->save();
    }

    private function sendStateChangeNotification(): void
    {
        $data = [
            $this->user->getGUID(),
            $this->user->getUserState(),
            $this->user->getUserStateToday(),
            $this->userState->getStateChange(),
            $this->estimateStateChange
        ];

        error_log(implode('|', $data));

        if ($this->estimateStateChange < 0 && $this->userState->getStateChange() < 0) {
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
