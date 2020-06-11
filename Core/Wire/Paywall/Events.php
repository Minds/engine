<?php

namespace Minds\Core\Wire\Paywall;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Features;
use Minds\Core\Events\Dispatcher;
use Minds\Entities\User;

class Events
{
    /** @var Features\Managers */
    private $featuresManager;

    public function __construct($featuresManager = null)
    {
        $this->featuresManager = $featuresManager;
    }

    public function register()
    {
        /**
         * Removes important export fields if marked as paywall
         */
        Dispatcher::register('export:extender', 'activity', function ($event) {
            if (!$this->featuresManager) { // Can not use DI in constructor due to init races
                $this->featuresManager = Di::_()->get('Features\Manager');
            }

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
                $export['blurb'] = null;

                if (!$this->featuresManager->has('paywall-2020')) {
                    $export['custom_type'] = null;
                    $export['custom_data'] = null;
                    $export['thumbnail_src'] = null;
                    $export['perma_url'] = null;
                    $export['title'] = null;
                }

                $dirty = true;
            }

            if (
                $activity->remind_object &&
                (int) $activity->remind_object['paywall'] &&
                $activity->remind_object['owner_guid'] != $currentUser
            ) {
                $export['remind_object'] = $activity->remind_object;
                $export['remind_object']['message'] = null;
                $export['remind_object']['blurb'] = null;
                
                if (!$this->featuresManager->has('paywall-2020')) {
                    $export['remind_object']['custom_type'] = null;
                    $export['remind_object']['custom_data'] = null;
                    $export['remind_object']['thumbnail_src'] = null;
                    $export['remind_object']['perma_url'] = null;
                    $export['remind_object']['title'] = null;
                }

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
