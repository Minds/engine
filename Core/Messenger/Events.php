<?php
/**
 * Messenger event handlers
 */
namespace Minds\Core\Messenger;

use Minds\Core;
use Minds\Core\Sockets;
use Minds\Api;
use Minds\Entities\User;
use Minds\Entities;
use Minds\Helpers;
use Minds\Core\Messenger;
use Minds\Core\Di\Di;
use Minds\Core\Security\Block;

class Events
{
    /** @var Block\Manager */
    protected $blockManager;

    public function __construct($blockManager = null)
    {
        $this->blockManager = $blockManager ?? Di::_()->get('Security\Block\Manager');
    }

    public function setup()
    {
        /**
        * if it's a mutual match then create a conversation
        */
        Core\Events\Dispatcher::register('subscribe', 'all', function ($event) {
            $params = $event->getParameters();

            $isMutual = (new Helpers\Subscriptions)->isMutual($params['user_guid'], $params['to_guid']);

            if ($isMutual) {
                $conversation = new Entities\Conversation();
                $conversation
                    ->setParticipant($params['user_guid'])
                    ->setParticipant($params['to_guid'])
                    ->saveToLists();
            }
        });

        /**
         * Extend User->export()
         */
        Core\Events\Dispatcher::register('export:extender', 'all', function ($event) {
            $params = $event->getParameters();

            if ($params['entity'] instanceof User) {
                $keystore = (new Messenger\Keystore())
                    ->setUser($params['entity']);

                if ($keystore->getPrivateKey()) {
                    $export = $event->response() ?: [];
                    $export['chat'] = true;
                    $event->setResponse($export);
                }
            }
        });

        /**
         * Extend Entity mapper
         */
        Core\Events\Dispatcher::register('entities:map', 'all', function ($event) {
            $params = $event->getParameters();

            if (($params['row'] ?? null) && isset($params['row']->subtype)) {
                if ($params['row']->subtype == 'message') {
                    $event->setResponse(new Entities\Message($params['row']));
                }
            }
        });

        /**
         * Extend ACL for Messages entity checks
         */
        Core\Events\Dispatcher::register('acl:read', 'all', function ($event) {
            $params = $event->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            if ($entity instanceof Entities\Message) {
                if (in_array($user->guid, array_keys($entity->getMessages()), false)) {
                    $event->setResponse(true);
                } else {
                    $event->setResponse(false);
                }
            }
            if ($entity instanceof Entities\Conversation) {
                if (in_array($user->guid, $entity->getParticipants(), false)) {
                    foreach ($entity->getParticipants() as $participantGuid) {
                        $blockEntry = (new Block\BlockEntry())
                            ->setActorGuid($user->getGuid())
                            ->setSubjectGuid($participantGuid);
                    
                        if ($this->blockManager->hasBlocked($blockEntry)) {
                            return $event->setResponse(false);
                        }
                    }

                    $event->setResponse(true);
                } else {
                    $event->setResponse(false);
                }
            }
        });

        Core\Events\Dispatcher::register('acl:block', 'all', function ($event) {
            $params = $event->getParameters();
            $from = $params['from'];
            $user = $params['user'];

            if (!$from || !$user) {
                return;
            }

            $isMutual = (new Helpers\Subscriptions)->isMutual($from, $user);

            try {
                (new Sockets\Events())
                  ->setUser($from)
                  ->emit('block', (string) $user);
            } catch (\Exception $e) { /* TODO: To log or not to log */
            }
        });

        Core\Events\Dispatcher::register('acl:unblock', 'all', function ($event) {
            $params = $event->getParameters();
            $from = $params['from'];
            $user = $params['user'];

            if (!$from || !$user) {
                return;
            }

            $isMutual = (new Helpers\Subscriptions)->isMutual($from, $user);

            try {
                (new Sockets\Events())
                  ->setUser($from)
                  ->emit('unblock', (string) $user);
            } catch (\Exception $e) { /* TODO: To log or not to log */
            }
        });
    }
}
