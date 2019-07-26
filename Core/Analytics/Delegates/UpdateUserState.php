<?php

namespace Minds\Core\Analytics\Delegates;

use Minds\Core\Analytics\UserStates\UserState;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Notification\Notification;
use Minds\Entities\User;

class UpdateUserState
{
    /**
     * @var User
     */
    private $user;
    /**
     * @var UserState
     */
    private $userState;

    public function __construct(UserState $userState, User $user = null)
    {
        $this->userState = $userState;
        $this->user = $user ?? new User($userState->getUserGuid());
    }

    public function update(): void
    {
        $this->updateUserEntity();
        $this->sendStateChangeNotification();
    }

    private function updateUserEntity(): void
    {
        $this->user->setUserState($this->userState->getState())
            ->setUserStateUpdatedMs($this->userState->getReferenceDateMs())
            ->save();
    }

    private function sendStateChangeNotification(): void
    {
        if ($this->userState->getStateChange() !== 0) {
            $notificationView = ($this->userState->getStateChange() > 0) ?
                'reward_state_increase' : 'reward_state_decrease';
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
