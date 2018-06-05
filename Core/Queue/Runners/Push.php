<?php
namespace Minds\Core\Queue\Runners;

use Minds\Core\Di\Di;
use Minds\Core\Data;
use Minds\Core\Queue\Interfaces;
use Minds\Core\Queue;
use Minds\Core\Notification\Settings;
use Minds\Entities\User;
use Surge;

/**
 * Push notifications runner
 */

class Push implements Interfaces\QueueRunner
{
    public function run()
    {
        $client = Queue\Client::Build();
        $client->setQueue("Push")
               ->receive(function ($data) {
                   echo "[push]: Received a push notification \n";

                   $data = $data->getData();
                   $keyspace = $data['keyspace'];

                   //for multisite support.
                   global $CONFIG;
                   $CONFIG->cassandra->keyspace = $keyspace;

                   $config = Di::_()->get('Config');
                   $googleConfig = $config->get('google');
                   $appleConfig = $config->get('apple');

                   try {
                       $config = new Surge\Config([
                        'Apple' => [
                          'cert' => $appleConfig['cert'],
                          'sandbox' => $appleConfig['sandbox'],
                        ],
                        'Google' => [
                          'api_key' => $googleConfig['push'],
                        ]
                      ]);

                       $type = $data['type'];

                      //get notification settings for this user
                      $toggles = (new Settings\PushSettings())
                        ->setUserGuid($data['user_guid'])
                        ->getToggles();
                       if ($type && !isset($toggles[$type]) || $toggles[$type] === false) {
                           echo "[push]: {$data['user_guid']} has disabled $type notifications \n";
                           return false;
                       }

                       $user = new user($data['user_guid'], false);

                       if (!$user->surge_token) {
                           echo "[push]: $user->username hasn't configured push yet.. not sending \n";
                           return false;
                       }

                       if (!$data['message']) {
                           return false;
                       }

                       if (!isset($data['json']) || !$data['json']) {
                           $data['json'] = [];
                       }

                       $data['json'] = array_merge([
                           'user_guid' => (string) $data['user_guid'],
                           'entity_guid' => (string) $data['entity_guid'],
                           'child_guid' => (string) $data['child_guid'],
                           'entity_type' => $data['entity_type'],
                           'parent_guid' => (string) $data['parent_guid'],
                           'type' => $data['type'],
                       ], $data['json']);

                       $message = Surge\Messages\Factory::build($user->surge_token)
                          ->setTitle($data['title'])
                          ->setBigPicture($data['big_picture'])
                          ->setBadge($data['badge'])
                          ->setLargeIcon($data['large_icon'])
                          ->setGroup($data['group'])
                          ->setMessage($data['message'])
                          ->setURI(isset($data['uri']) ? $data['uri'] : 'chat')
                          ->setSound(isset($data['sound']) ? $data['sound'] : 'default')
                          ->setJsonObject($data['json']);

                       Surge\Surge::send($message, $config);

                       echo "[push]: delivered $user->guid \n";
                   } catch (\Exception $e) {
                       echo "Failed to send push notification \n";
                   }
               });
        $this->run();
    }
}
