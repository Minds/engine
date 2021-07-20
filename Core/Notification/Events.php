<?php

namespace Minds\Core\Notification;

use Minds\Common\Regex;
use Minds\Entities;
use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Queue;
use Minds\Core\Session;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Security\Block\BlockEntry;

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
         * Create a notification upon @mentioning on activities or comments
         */
        Dispatcher::register('create', 'all', function ($hook, $type, $entity) {
            if ($type != 'activity' && $type != 'comment') {
                return;
            }
            
            $message = "";

            if ($entity->message) {
                $message = $entity->message;
            }

            if ($type == 'comment') {
                $message = $entity->getBody();
            }

            if ($entity->title) {
                $message .= $entity->title;
            }

            if (preg_match_all(Regex::AT, $message, $matches)) {
                $usernames = $matches[1];
                $toGuids = [];
                $to = [];

                foreach ($usernames as $username) {
                    $user = new Entities\User(strtolower($username));

                    if ($user->guid && Core\Security\ACL::_()->interact($user, Core\Session::getLoggedinUser())) {
                        $to[] = $user;
                        $toGuids[] = $user->guid;
                    }

                    //limit of tags notifications: 5
                    if (count($to) >= 5) {
                        break;
                    }
                }

                $params = [
                    'title' => $message,
                ];

                if ($entity->type === 'comment') {
                    $params['focusedCommentUrn'] = $entity->getUrn();
                }

                if ($to) {
                    // Tag event
                    $actionEventTopic = new ActionEventsTopic();
                    foreach ($to as $taggedUser) {
                        // Entity owner will already be getting notified of comment
                        if ($taggedUser->getGuid() !== $entity->getOwnerGuid()) {
                            $actionEvent = new ActionEvent();
                            $actionEvent->setAction(ActionEvent::ACTION_TAG)
                            ->setUser(Core\Session::getLoggedinUser()) // Who is tagging
                            ->setEntity($taggedUser) // The tagged person
                            ->setActionData([
                                'tag_in_entity_urn' => $entity->getUrn(),
                            ]);
                            $actionEventTopic->send($actionEvent);
                        }
                    }
                }
            }
        });
    }
}
