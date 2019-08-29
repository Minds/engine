<?php

namespace Minds\Core\Wire\Paywall;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Events\Dispatcher;
use Minds\Entities\User;

class Events
{
    public function register()
    {
        /**
         * Removes important export fields if marked as paywall
         */
        Dispatcher::register('export:extender', 'activity', function ($event) {
            $params = $event->getParameters();
            $activity = $params['entity'];
            if ($activity->type != 'activity') {
                return;
            }
            $export = $event->response() ?: [];
            $currentUser = Session::getLoggedInUserGuid();

            $dirty = false;

            if ($activity->isPaywall() && $activity->owner_guid != $currentUser) {
                $export['message'] = null;
                $export['custom_type'] = null;
                $export['custom_data'] = null;
                $export['thumbnail_src'] = null;
                $export['perma_url'] = null;
                $export['blurb'] = null;
                $export['title'] = null;

                $dirty = true;
            }

            if (
                $activity->remind_object &&
                (int) $activity->remind_object['paywall'] &&
                $activity->remind_object['owner_guid'] != $currentUser
            ) {
                $export['remind_object'] = $activity->remind_object;
                $export['remind_object']['message'] = null;
                $export['remind_object']['custom_type'] = null;
                $export['remind_object']['custom_data'] = null;
                $export['remind_object']['thumbnail_src'] = null;
                $export['remind_object']['perma_url'] = null;
                $export['remind_object']['blurb'] = null;
                $export['remind_object']['title'] = null;

                $dirty = true;
            }

            if ($dirty) {
                return $event->setResponse($export);
            }

            if (!$currentUser) {
                return;
            }
        });

        /**
         * Blogs need more exportable fields for paywall
         */
        Dispatcher::register('export:extender', 'blog', function (Event $event) {
            $params = $event->getParameters();
            /** @var Core\Blogs\Blog $blog */
            $blog = $params['entity'];
            $export = $event->response() ?: [];
            $currentUser = Session::getLoggedInUserGuid();

            $dirty = false;

            if ($blog->isPaywall() && $blog->owner_guid != $currentUser) {
                $export['description'] = '';
                $export['body'] = '';
                $dirty = true;
            }

            if ($dirty) {
                return $event->setResponse($export);
            }

            if (!$currentUser) {
                return;
            }
        });

        /**
         * Wire paywall hooks. Allows access if sent wire or is plus
         */
        Dispatcher::register('acl:read', 'object', function ($event) {
            $params = $event->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            if (!method_exists($entity, 'getFlag') || !$entity->getFlag('paywall')) {
                return;
            }

            if (!$user) {
                return false;
            }

            //Plus hack

            if ($entity->owner_guid == '730071191229833224') {
                $plus = (new Core\Plus\Subscription())->setUser($user);

                if ($plus->isActive()) {
                    return $event->setResponse(true);
                }
            }

            try {
                $isAllowed = Di::_()->get('Wire\Thresholds')->isAllowed($user, $entity);
            } catch (\Exception $e) {
            }

            if ($isAllowed) {
                return $event->setResponse(true);
            }

            return $event->setResponse(false);
        });

        /*
         * Legcacy compatability for exclusive content
         */
        Dispatcher::register('export:extender', 'activity', function ($event) {
            $params = $event->getParameters();
            $activity = $params['entity'];
            if ($activity->type != 'activity') {
                return;
            }
            $export = $event->response() ?: [];
            $currentUser = Session::getLoggedInUserGuid();

            if ($activity->isPaywall() && !$activity->getWireThreshold()) {
                $export['wire_threshold'] = [
                  'type' => 'money',
                  'min' => $activity->getOwnerEntity()->getMerchant()['exclusive']['amount'],
                ];

                return $event->setResponse($export);
            }
        });
    }
}
