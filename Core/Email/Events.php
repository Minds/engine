<?php
/**
 * Email events.
 */

namespace Minds\Core\Email;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Analytics\UserStates\UserActivityBuckets;
use Minds\Core\Email\V2\Delegates\DigestSender;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Interfaces\SenderInterface;

class Events
{
    private EntitiesBuilder $entitiesBuilder;

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
        });

        Dispatcher::register('user_state_change', UserActivityBuckets::STATE_RESURRECTED, function ($opts) {
            error_log('user_state_change resurrected');
        });

        Dispatcher::register('user_state_change', UserActivityBuckets::STATE_COLD, function ($opts) {
            $this->sendCampaign(new DigestSender(), $opts->getParameters());
        });
    }

    private function sendCampaign(SenderInterface $sender, $params)
    {
        $user = $this->getEntitiesBuilder()->single($params['user_guid'], $params['cache'] ?? true);
        if ($user instanceof User) {
            $sender->send($user);
        }
    }

    private function getEntitiesBuilder(): EntitiesBuilder
    {
        return $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
    }
}
