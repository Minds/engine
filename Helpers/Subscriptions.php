<?php
namespace Minds\Helpers;

use Minds\Core;
use Minds\Core\Security;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events;
use Minds\Core\Subscriptions\Manager;
use Minds\Entities\User;
use Minds\Helpers\Wallet;

/**
 * Helper for Users subscriptions
 * @todo Avoid static and use proper DI
 */
class Subscriptions
{
    /**
     * Subscribe a user to a user
     * @param  mixed $user_guid - the user who is doing the action, eg. me
     * @param  mixed $to_guid   - the user to subscribe to
     * @param  array $data      - metadata. Optional.
     * @return boolean
     */
    public static function subscribe($user_guid, $to_guid, $data = [])
    {
        if (static::isSubscribed($user_guid, $to_guid)) {
            return false;
        }

        $manager = Di::_()->get(Manager::class);
        $manager->setSubscriber((new User())->set('guid', $user_guid));
        $return = $manager->subscribe((new User())->set('guid', $to_guid));

        return (bool) $return;
    }

    /**
     * Unsubscribes a user from a user
     * @param  mixed $user - the user who is doing the action, eg. me
     * @param  mixed $from - the user to unsubscribe from
     * @return boolean
     */
    public static function unSubscribe($user, $from)
    {
        if (!static::isSubscribed($user, $from)) {
            return false;
        }

        $manager = Di::_()->get(Manager::class);
        $manager->setSubscriber((new User())->set('guid', $user));
        $return = $manager->unSubscribe((new User())->set('guid', $from));

        return (bool) $return;
    }

    /**
     * Checks if a user is subscribed to another.
     * @param  mixed $user - the user who is doing the action, eg. me
     * @param  mixed $to   - the user to check subscription to
     * @return boolean
     */
    public static function isSubscribed($user, $to)
    {
        /** @var Manager */
        $manager = Di::_()->get(Manager::class);
        $manager->setSubscriber((new User())->set('guid', $user));
        return $manager->isSubscribed((new User())->set('guid', $to));
    }


    public static function registerEvents()
    {
        Events\Dispatcher::register('subscription:dispatch', 'all', function (Events\Event $event) {
            $params = $event->getParameters();

            $currentUser = Di::_()->get(EntitiesBuilder::class)->single($params['currentUser']);
            $guids = $params['guids'];

            if (!is_array($guids)) {
                $guids = [ $guids ];
            }

            if (!$currentUser || !($currentUser instanceof User)) {
                $event->setResponse([ 'done' => false, 'error' => 'INVALID_USER' ]);
                return;
            }

            $results = [];

            foreach ($guids as $guid) {
                try {
                    $target = Di::_()->get(EntitiesBuilder::class)->single($guid);

                    if (!($target instanceof User) || !$target->guid) {
                        $results[] = [
                            'guid' => $guid,
                            'done' => false,
                            'error' => 'INVALID_TARGET'
                        ];
                        continue;
                    }

                    $canSubscribe = Security\ACL::_()->interact($currentUser, $target) &&
                        Security\ACL::_()->interact($target, $currentUser);

                    if (!$canSubscribe) {
                        $results[] = [
                            'guid' => $guid,
                            'done' => false,
                            'error' => 'BLOCKED_USER'
                        ];
                        continue;
                    }

                    $success = $currentUser->subscribe($target->guid);

                    $results[] = [ 'guid' => $guid, 'done' => true ];
                } catch (\Exception $e) {
                    $results[] = [
                        'guid' => $guid,
                        'done' => false,
                        'exception' => $e
                    ];
                }
            }

            $event->setResponse([ 'done' => true, 'results' => $results ]);
        });
    }

    /**
     * Checks if $user and $to are mutual subscribers
     * @param  mixed   $user
     * @param  mixed   $to
     * @return boolean
     */
    public function isMutual($user, $to)
    {
        $friendsof = new Core\Data\Call('friendsof');
        $mutual = false;

        if ($user instanceof User) {
            $user = $user->guid;
        }

        if ($to instanceof User) {
            $to = $to->guid;
        }

        if ($item = $friendsof->getRow($user, [ 'offset' => $to, 'limit' => 1 ])) {
            if ($item && key($item) == $to) {
                $mutual = true;
            }
        }

        return $mutual;
    }
}
