<?php
/**
 * Email events.
 */

namespace Minds\Core\Email;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Analytics\UserStates\UserActivityBuckets;

use Minds\Core\Email\V2\Campaigns\Recurring\WelcomeComplete\WelcomeComplete;
use Minds\Core\Email\V2\Campaigns\Recurring\WelcomeIncomplete\WelcomeIncomplete;
use Minds\Core\Email\V2\Delegates\ConfirmationSender;
use Minds\Core\Email\V2\Delegates\WeMissYouSender;
use Minds\Core\Email\V2\Delegates\DigestSender;
use Minds\Entities\User;
use Minds\Core\Email\Manager;
use Minds\Core\Suggestions\Manager as SuggestionManager;
use Minds\Interfaces\SenderInterface;

class Events
{
    public function register()
    {
        Dispatcher::register('user_state_change', 'all', function ($opts) {
            error_log('user_state_change all');
        });

        Dispatcher::register('user_state_change', UserActivityBuckets::STATE_CASUAL, function ($opts) {
            error_log('user_state_change casual');
        });

        Dispatcher::register('user_state_change', UserActivityBuckets::STATE_CORE, function ($opts) {
            error_log('user_state_change core');
        });

        Dispatcher::register('user_state_change', UserActivityBuckets::STATE_CURIOUS, function ($opts) {
            error_log('user_state_change curious');
        });

        Dispatcher::register('user_state_change', UserActivityBuckets::STATE_NEW, function ($opts) {
            error_log('user_state_change new');
            // $this->sendCampaign(new Delegates\WelcomeSender(), $opts->getParameters());
        });

        Dispatcher::register('user_state_change', UserActivityBuckets::STATE_RESURRECTED, function ($opts) {
            error_log('user_state_change resurrected');
        });

        Dispatcher::register('user_state_change', UserActivityBuckets::STATE_COLD, function ($opts) {
            $this->sendCampaign(new DigestSender(), $opts->getParameters());
        });

        Dispatcher::register('welcome_email', 'all', function ($opts) {
            // $this->sendCampaign(new Delegates\WelcomeSender(), $opts->getParameters());
        });

        Dispatcher::register('confirmation_email', 'all', function ($opts) {
            $this->sendCampaign(new ConfirmationSender(), $opts->getParameters());
        });
    }

    private function sendCampaign(SenderInterface $sender, $params)
    {
        $user = new User($params['user_guid'], $params['cache'] ?? true);
        $sender->send($user);
    }
}
