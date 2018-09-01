<?php
/**
 * Minds Newsfeed API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v2\messenger;

use Minds\Core;
use Minds\Core\Session;
use Minds\Core\Security;
use Minds\Entities;
use Minds\Helpers;
use Minds\Core\Messenger;
use Minds\Core\Messenger\Messages;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Sockets;

class conversations implements Interfaces\Api
{

    /**
     * Returns the conversations or conversation
     * @param array $pages
     *
     * API:: /v1/conversations
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        $response = [];

        if (isset($pages[0])) {
            $response = $this->getConversation($pages);
        } else {
            $response = $this->getList();
        }


        return Factory::response($response);
    }

    private function getConversation($pages)
    {
        $response = [];
        $delimiter = ':';

        $me = Core\Session::getLoggedInUser();

        if ($pages[0]) {
            $split_guid = explode($delimiter, $pages[0]);
            sort($split_guid);
            $guids = join($delimiter, $split_guid);
        }

        $conversation = (new Entities\Conversation())
          ->loadFromGuid($guids);

        if (!Security\ACL::_()->read($conversation)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You do not have permissions to read this message'
            ]);
        }

        $messages = (new Messenger\Messages)
          ->setConversation($conversation);

        if ($conversation) {
            $response = $conversation->export();
            $blocked = false;
            $unavailable = false;

            if (is_array($response['participants']) && false) {
                foreach ($response['participants'] as $participant) {
                    if (!Security\ACL::_()->interact(Core\Session::getLoggedInUser(), $participant['guid'])) {
                        $blocked = true;
                        break;
                    }
                }

                if (!$blocked) {
                    foreach ($response['participants'] as $participant) {
                        if (!Security\ACL::_()->interact($participant['guid'], Core\Session::getLoggedInUser())) {
                            $unavailable = true;
                            break;
                        }
                    }
                }
            }

            $response['blocked'] = $blocked;
            $response['unavailable'] = $unavailable;
        }

        // Check if all participants have their public keys set before trying to fetch the conversation
        $invalidPublicKeyUsers = $this->getInvalidPublicKeyUsers($conversation->getParticipants());
        if ($invalidPublicKeyUsers) {
            $response['invitable'] = Factory::exportable($invalidPublicKeyUsers) ?: false;
            $response['messages'] = [];
            $response['load-next'] = '';
            $response['load-previous'] = '';
            return $response;
        }

        $limit = isset($_GET['limit']) ? $_GET['limit'] : 6;
        $offset = isset($_GET['offset']) ? $_GET['offset'] : "";
        $finish = isset($_GET['finish']) ? $_GET['finish'] : "";
        $messages = $messages->getMessages($limit, $offset, $finish);

        if ($messages) {
            foreach ($messages as $k => $message) {
                if (!isset($_GET['access_token'])) {
                    $_GET['decrypt'] = true;
                }
                if (isset($_GET['decrypt']) && $_GET['decrypt']) {
                    $messages[$k]->decrypt(Core\Session::getLoggedInUser(), $_COOKIE['messenger-secret']);
                } else {
                    //support legacy clients
                    $messages[$k]->message = $messages[$k]->getMessage(Core\Session::getLoggedInUserGuid());
                }
            }

            $conversation->markAsRead(Session::getLoggedInUserGuid());

            $messages = array_reverse($messages);
            $response['messages'] = Factory::exportable($messages);
            $response['load-next'] = (string) end($messages)->guid;
            $response['load-previous'] = (string) reset($messages)->guid;
        }

        $keystore = new Messenger\Keystore();

        if (!$offset || isset($_GET['access_token'])) {
            //return the public keys
            $response['publickeys'] = [];
            $response['publickeys'][Session::getLoggedInUserGuid()] = $keystore->setUser(Core\Session::getLoggedInUser())->getPublicKey();
            foreach ($conversation->getParticipants() as $participant_guid) {
                if ($participant_guid == Session::getLoggedInUserGuid()) {
                    continue;
                }
                $response['publickeys'][(string) $participant_guid] = $keystore->setUser($participant_guid)->getPublicKey();
                $response['publickeys'][$conversation->getGuid()] = $keystore->setUser($participant_guid)->getPublicKey();
            }
        }

        return $response;
    }

    private function getList()
    {
        $response = [];

        $conversations = (new Messenger\Conversations)->getList(12, $_GET['offset']);

        if ($conversations) {
            $response['conversations'] = Factory::exportable($conversations);

            //mobile polyfill
            foreach ($response['conversations'] as $k => $v) {
                $response['conversations'][$k]['subscribed'] = true;
                $response['conversations'][$k]['subscriber'] = true;

                //if no username, user has vanished
                if (!$response['conversations'][$k]['username']) {
                    unset($response['conversations'][$k]);
                }
            }

            //order needs reseting due to possible deletes
            $response['conversations'] = array_values($response['conversations']);

            end($conversations);
            $response['load-next'] = (int) $_GET['offset'] + count($conversations);
            $response['load-previous'] = (int) $_GET['offset'] - count($conversations);
        }
        return $response;
    }

    public function post($pages)
    {
        Factory::isLoggedIn();

        //error_log("got a message to send");
        $conversation = new Entities\Conversation();

        $guid = "";

        $split_guid = explode(":", $pages[0]);
        sort($split_guid);
        $guid = join(":", $split_guid);

        $conversation->setGuid($guid);

        if (!Security\ACL::_()->read($conversation)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You do not have permissions to post to this conversation'
            ]);
        }


        $message = (new Entities\Message())
          ->setConversation($conversation);

        // Block check
        foreach ($conversation->getParticipants() as $guid) {
            if (Session::getLoggedInUserGuid() == $guid) {
                continue;
            }
            
            if (!Security\ACL::_()->interact($guid, Session::getLoggedInUserGuid())) {
                // At least one people blocked on this conversation
                // Means that we cannot chat here
                return Factory::response([
                    'unavailable' => true
                ]);
            }
        }


        if (isset($_POST['encrypt']) && $_POST['encrypt']) {
            $message->setMessage($_POST['message']);
            $message->encrypt();
        } else {
            $messages = [];
            foreach ($conversation->getParticipants() as $guid) {
                $key = "message:$guid";
                $messages[$guid] = base64_encode(base64_decode(rawurldecode($_POST[$key]))); //odd bug sometimes with device base64..
            }
            $message->setMessages($messages, true);
            $message->message = $messages[Session::getLoggedInUserGuid()];
        }

        if (!$this->hasValidMessages($message)) {
            // Likely, a participant hasn't set their public key
            $origins = $this->getInvalidMessageOrigins($message);
            $this->sendInvite($origins);

            return Factory::response([
                'invalid' => true
            ]);
        }

        $message->save();
        $conversation->markAsUnread(Session::getLoggedInUserGuid());
        $conversation->markAsRead(Session::getLoggedInUserGuid());

        $response["message"] = $message->export();
        $emit = $response['message'];
        unset($emit['message']);

        $emit['tabId'] = isset($_POST['tabId']) ? $_POST['tabId'] : null;

        try {
            (new Sockets\Events())
              ->setRoom($conversation->buildSocketRoomName())
              ->emit('pushConversationMessage', (string) $conversation->getGuid(), $emit);
        } catch (\Exception $e) { /* TODO: To log or not to log */
        }

        foreach ($conversation->getParticipants() as $participant) {
            if ($participant == Session::getLoggedInUserGuid()) {
                continue;
            }
            Core\Queue\Client::build()->setExchange("mindsqueue")
                                      ->setQueue("Push")
                                      ->send([
                                            "user_guid"=>$participant,
                                            "message"=>"You have a new message.",
                                            "uri" => 'chat',
                                            'type' => 'message'
                                        ]);
        }

        $this->emitSocketTouch($conversation);

        return Factory::response($response);
    }

    public function put($pages)
    {
        Factory::isLoggedIn();

        if (!Security\ACL::_()->interact($pages[1], Session::getLoggedInUserGuid())) {
            return Factory::response([
                'unavailable' => true
            ]);
        }

        switch ($pages[0]) {
            case 'invite':
                // Handle 1 invite at a time (for now)
                if (!$this->getInvalidPublicKeyUsers([ $pages[1] ])) {
                    // Already setup
                    break;
                }

                $this->sendInvite([ $pages[1] ]);
                break;

            case 'call':
               \Minds\Core\Queue\Client::build()->setExchange("mindsqueue")
                                                ->setQueue("Push")
                                                ->send(array(
                                                     "user_guid"=>$pages[1],
                                                    "message"=> \Minds\Core\Session::getLoggedInUser()->name . " is calling you.",
                                                    "uri" => 'call',
                                                    "sound" => 'ringing-1.m4a',
                                                    "json" => json_encode(array(
                                                        "from_guid"=>\Minds\Core\Session::getLoggedInUser()->guid,
                                                        "from_name"=>\Minds\Core\Session::getLoggedInUser()->name
                                                    ))
                                                ));
                break;
            case 'no-answer':
              //leave a notification
              $conversation = new entities\conversation(elgg_get_logged_in_user_guid(), $pages[1]);
              $message = new entities\CallMissed($conversation);
              $message->save();
              $conversation->update();
              Core\Queue\Client::build()->setExchange("mindsqueue")
                                        ->setQueue("Push")
                                        ->send(array(
                                              "user_guid"=>$pages[1],
                                              "message"=> \Minds\Core\Session::getLoggedInUser()->name . " tried to call you.",
                                              "uri" => 'chat',

                                             ));
              break;
            case 'ended':
              $conversation = new entities\conversation(elgg_get_logged_in_user_guid(), $pages[1]);
              $message = new entities\CallEnded($conversation);
              $message->save();
              break;
        }

        return Factory::response(array());
    }

    public function delete($pages)
    {
        Factory::isLoggedIn();

        $user = Core\Session::getLoggedInUser();
        $response = [];

        $conversation = new Entities\Conversation();
        $conversation->setGuid($pages[0]);

        if (!Security\ACL::_()->read($conversation)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You do not have permissions to delete this message'
            ]);
        }

        $message = (new Entities\Message())
          ->setConversation($conversation);

        $message->deleteAll();
        $conversation->saveToLists();

        try {
            (new Sockets\Events())
              ->setRoom($conversation->buildSocketRoomName())
              ->emit('clearConversation', (string) $conversation->getGuid(), [
                  'guid' => (string) $user->guid,
                  'name' => $user->name
              ]);
        } catch (\Exception $e) { /* TODO: To log or not to log */
        }

        $this->emitSocketTouch($conversation);

        return Factory::response($response);
    }

    private function emitSocketTouch($conversation)
    {
        if ($conversation->getParticipants()) {
            $users = [];

            foreach ($conversation->getParticipants() as $guid) {
                if ($guid == Core\Session::getLoggedInUserGuid()) {
                    continue;
                }

                $users[] = $guid;
            }

            if (!$users) {
                return;
            }

            try {
                (new Sockets\Events())
                ->setUsers($users)
                ->emit('touchConversation', (string) $conversation->getGuid());
            } catch (\Exception $e) { /* TODO: To log or not to log */
            }
        }
    }

    private function hasValidMessages($message) {
        $messages = $message->getMessages();

        foreach ($messages as $guid => $message) {
            if ($message === false) {
                return false;
            }
        }

        return true;
    }
    
    private function getInvalidMessageOrigins($message) {
        $messages = $message->getMessages();
        $origins = [];

        foreach ($messages as $guid => $message) {
            if ($message === false) {
                $origins[] = $guid;
            }
        }

        return $origins;
    }

    private function getInvalidPublicKeyUsers(array $users) {
        $keystore = new Messenger\Keystore();
        $invalid = [];

        foreach ($users as $user) {
            $user = !is_object($user) ? Entities\Factory::build($user) : $user;

            if ($keystore->setUser($user)->getPublicKey()) {
                continue;
            }

            $invalid[] = $user;
        }

        return $invalid;
    }

    private function sendInvite($users) {
        if (!is_array($users)) {
            $users = [ $users ];
        }

        foreach ($users as $user) {
            if (is_object($user)) {
                $user = $user->guid;
            }

            Core\Events\Dispatcher::trigger('notification', 'Messenger', [
                'to' => [ $user ],
                'from' => Session::getLoggedInUserGuid(),
                'notification_view' => 'messenger_invite',
                'params' => [ 'username' => Session::getLoggedInUser()->username ]
            ]);
        }
    }
}
