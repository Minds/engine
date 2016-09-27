<?php
namespace Minds\Core\Notification;

use Minds\Entities;
use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Queue;
use Minds\Core\Session;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Core\Notification\Extensions\Push;

use Minds\Helpers;
use Minds\Core\Sockets;

class Events
{

    /**
     * Centralized method to register Event handlers related to notifications
     * @return null
     */
    public static function registerEvents()
    {

        /**
         * Create a notification when triggered
         */
        Dispatcher::register('notification', 'all', function (Event $event) {
            $params = $event->getParameters();
            $from = null;

            if (isset($params['from'])) {
                $from = $params['from'];
            }

            if ($params['entity'] && !is_object($params['entity'])) {
                $params['entity'] = Entities\Factory::build($params['entity']);
            }

            $from_user = Entities\Factory::build($from ?: Session::getLoggedInUser(), [
                'cache' => true
            ]);

            $notification = (new Entities\Notification())
                ->setEntity($params['entity'])
                ->setFrom($from_user)
                ->setNotificationView($params['notification_view'])
                ->setDescription(isset($params['description']) ? $params['description'] : '')
                ->setParams($params['params'])
                ->setTimeCreated(time());

            try {
                Queue\Client::build()
                  ->setExchange('mindsqueue')
                  ->setQueue('NotificationDispatcher')
                  ->send([
                      'notification' => serialize($notification),
                      'to' => $params['to']
                  ]);
            } catch (\Exception $e) {}

            $event->setResponse([
                $notification
            ]);

	});

        /**
         * Create a notification upon @mentioning on activities or comments
         */
        Dispatcher::register('create', 'all', function ($hook, $type, $params = []) {
            if ($type != 'activity' && $type != 'comment') {
                return;
            }

            if ($params->message) {
                $message = $params->message;
            }

            if ($type == 'comment') {
                $message = $params->description;
            }

            if ($params->title) {
                $message .= $params->title;
            }

            $remind_owner_username = null;

            if ($type == 'activity' && isset($params->remind_object['ownerObj']['username'])) {
                $remind_owner_username = $params->remind_object['ownerObj']['username'];
            }

            if (preg_match_all('!@(.+)(?:\s|$)!U', $message, $matches)) {
                $usernames = $matches[1];
                $to = [];

                foreach ($usernames as $username) {
                    if ($remind_owner_username && $remind_owner_username == $username) {
                        // Don't send notification to the remind owner
                        // (they already received a notification)
                        continue;
                    }

                    $user = new Entities\User(strtolower($username));

                    if ($user->guid && !Core\Security\ACL\Block::_()->isBlocked(Core\Session::getLoggedinUser(), $user)) {
                        $to[] = $user->guid;
                    }
                }

                if ($to) {
                    Dispatcher::trigger('notification', 'all', [
                        'to' => $to,
                        'entity' => $params,
                        'notification_view' => 'tag',
                        'description' => $params->message,
                        'title' => $params->title
                    ]);
                }
            }
        });

        Dispatcher::register('notification:dispatch', 'all', function (Event $event) {
            $params = $event->getParameters();
            $notification = unserialize($params['notification']);

            if (!$notification instanceof Entities\Notification) {
                return;
            }

            $db = new Data\Call('entities_by_time');
            $notification->setDb($db);

            $entity = $notification->getEntity();
            if ($params['to'] && $entity && in_array($entity->type, [ 'activity', 'object' ])) {
                $muted = array_map([ __CLASS__, 'toString' ], (new Entity($entity))->getMutedUsers());
                $params['to'] = array_map([ __CLASS__, 'toString' ], $params['to']);
                $params['to'] = array_diff($params['to'], $muted);
            }

            $from_user = $notification->getFrom();
            if (is_numeric($from_user) || is_string($from_user)) {
                $from_user = Entities\Factory::build($from_user);
            }

            $manager = new Notifications();

            foreach ($params['to'] as $to_user) {
                if (is_numeric($to_user) || is_string($to_user)) {
                    $to_user = Entities\Factory::build((int) $to_user);
                }

                if (!$to_user) {
                    continue;
                }

                if ($from_user->guid && Core\Security\ACL\Block::_()->isBlocked($from_user, $to_user)) {
                    // echo "{$from_user->username} is blocked by {$to_user->username}, skipping.";
                    continue;
                }

                $notification->setTo($to_user)
                  ->setOwner($to_user)
                  ->save();


                $params = $notification->getParams();
                $params['notification_view']  = $notification->getNotificationView();
                Push::_()->queue([
                    'uri' => 'notification',
                    'from' => $notification->getFrom(),
                    'to' => $notification->getTo(),
                    'notification' => $notification,
                    'params' => $params
                ]);

                $manager->setUser($to_user)
                  ->increaseCounter($to_user);

                try {
                    (new Sockets\Events())
                    ->setUser($to_user)
                    ->emit('notification', (string) $notification->getGuid());
                } catch (\Exception $e) { /* TODO: To log or not to log */
                }

                echo "[notification][{$notification->getGuid()}]: Saved {$params['notification_view']} \n";
            }
        });

        /**
         * Cron events
         */
        Dispatcher::register('cron', 'minute', [ __CLASS__, 'cronHandler' ]);
        Dispatcher::register('cron', 'daily', [ __CLASS__, 'cronHandler' ]);
        Dispatcher::register('cron', 'weekly', [ __CLASS__, 'cronHandler' ]);
    }

    public static function cronHandler($hook, $type, $params, $return = null)
    {
        if (!isset($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] != 'localhost') {
            return false;
        }

        // TODO: [emi] Send email notifications
    }

    /**
     * Internal funcion. Typecasts to string.
     * @param  mixed $var
     * @return string
     */
    private static function toString($var)
    {
        return (string) $var;
    }
}
