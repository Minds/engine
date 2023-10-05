<?php
/**
 * BanDelegate.
 *
 * @author emi
 */

namespace Minds\Core\Channels\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\User;

class Ban
{
    public function __construct(
        protected ?EventsDispatcher $eventsDispatcher = null,
        protected ?Save $save = null,
    ) {
        $this->eventsDispatcher ??= Di::_()->get('EventsDispatcher');
        $this->save ??= new Save();
    }

    /**
     * @param User $user
     * @param string $banReason
     * @return bool
     */
    public function ban(User $user, $banReason = '', $refreshCache = true)
    {
        $user->ban_reason = $banReason;
        $user->banned = 'yes';
        $user->code = '';

        $saved = (bool) $this->save->setEntity($user)->withMutatedAttributes(['ban_reason', 'banned'])->save();

        if ($saved) {
            if ($refreshCache) {
                \cache_entity($user);
            }

            $this->eventsDispatcher->trigger('ban', 'user', $user);
        }

        return $saved;
    }
}
