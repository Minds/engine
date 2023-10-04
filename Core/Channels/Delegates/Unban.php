<?php
/**
 * Unban.
 *
 * @author emi
 */

namespace Minds\Core\Channels\Delegates;

use Minds\Core\Entities\Actions\Save;
use Minds\Entities\User;

class Unban
{
    /**
     * @param User $user
     * @return bool
     */
    public function unban(User $user, $refreshCache = true)
    {
        $user->ban_reason = '';
        $user->banned = 'no';

        $saved = (bool) (new Save())->setEntity($user)->withMutatedAttributes(['ban_reason', 'banned'])->save();

        if ($saved && $refreshCache) {
            \cache_entity($user);
        }

        return $saved;
    }
}
