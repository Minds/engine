<?php
/**
 * Email events.
 */

namespace Minds\Core\Email;

use Minds\Core\Analytics\UserStates\UserState;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Entities\User;
use Minds\Interfaces\SenderInterface;

class Events
{
    public function register()
    {
        Dispatcher::register('user_state_change', 'all', function ($opts) {
            error_log('user_state_change all');
        });

        Dispatcher::register('user_state_change', UserState::STATE_CASUAL, function ($opts) {
            error_log('user_state_change casual');
        });

        Dispatcher::register('user_state_change', UserState::STATE_CORE, function ($opts) {
            error_log('user_state_change core');
        });

        Dispatcher::register('user_state_change', UserState::STATE_CURIOUS, function ($opts) {
            error_log('user_state_change curious');
        });

        Dispatcher::register('user_state_change', UserState::STATE_NEW, function (Event $event) {
            error_log('user_state_change new');
            $this->sendCampaign(new Delegates\WelcomeSender(), $event->getParameters());
        });

        Dispatcher::register('user_state_change', UserState::STATE_RESURRECTED, function ($opts) {
            error_log('user_state_change resurrected');
        });

        Dispatcher::register('user_state_change', UserState::STATE_COLD, function (Event $event) {
            $this->sendCampaign(new Delegates\GoneColdSender(), $event->getParameters());
        });

        Dispatcher::register('welcome_email', 'all', function (Event $event) {
            $this->sendCampaign(new Delegates\WelcomeSender(), $event->getParameters());
        });

    }

    private function sendCampaign (SenderInterface $sender, $params) {
        $user = new User($params['user_guid']);
        $sender->send($user);
    }
}
