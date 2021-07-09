<?php

namespace Minds\Core\Wire\Paywall;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Features;
use Minds\Core\Events\Dispatcher;

class Events
{
    /** @var Features\Managers */
    private $featuresManager;

    /** @var SupportTier\Manager */
    private $supportTiersManager;

    /** @var Paywall\Manager */
    private $paywallManager;

    public function __construct($featuresManager = null, $supportTiersManager = null, $paywallManager = null)
    {
        $this->featuresManager = $featuresManager;
        $this->supportTiersManager = $supportTiersManager;
        $this->paywallManager = $paywallManager;
    }

    public function register()
    {
        /*
         * Removes important export fields if marked as paywall
         */
        Dispatcher::register('export:extender', 'all', function ($event) {
            if (!$this->featuresManager) { // Can not use DI in constructor due to init races
                $this->featuresManager = Di::_()->get('Features\Manager');
            }

            $params = $event->getParameters();
            $activity = $params['entity'];

            $export = $event->response() ?: [];
            $currentUser = Session::getLoggedInUserGuid();
            $currentUserEntity = Session::getLoggedInUser();

            $dirty = false;

            if (!$activity instanceof PaywallEntityInterface) {
                return;
            }

            if ($activity->isPaywall() &&
                $activity->getWireThreshold()['support_tier']['urn'] === Di::_()->get('Config')->plus['support_tier_urn'] &&
                $currentUserEntity &&
                $currentUserEntity->isPlus()
            ) {
                $activity->setPayWallUnlocked(true);
            }

            if ($activity->isPayWallUnlocked()) {

                // append description if paywall is unlocked.
                if ($activity->getSubtype() === 'blog') {
                    $export['description'] = $activity->getBody();
                }
                $export['paywall'] = false;
                $export['paywall_unlocked'] = true;
                $event->setResponse($export);
                return;
            }

            if ($activity->isPaywall() && $activity->owner_guid != $currentUser) {
                $export['blurb'] = $this->extractTeaser($activity->blurb);

                // don't export teaser for status posts
                if (!$this->isStatusPost($activity)) {
                    $export['message'] = $this->extractTeaser($activity->message);
                } else {
                    $export['message'] = null;
                }

                if (!$this->featuresManager->has('paywall-2020')) {
                    $export['custom_type'] = null;
                    $export['custom_data'] = null;
                    $export['thumbnail_src'] = null;
                    $export['perma_url'] = null;
                    $export['title'] = null;
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

        /*
         * Wire paywall hooks. Allows access if sent wire or is plus
         */
        Dispatcher::register('acl:read', 'object', function ($event) {
            $params = $event->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            if (!$entity->isPayWall()) {
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
         * Legacy compatability for exclusive content
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

        /**
         * Pair the support tier with the output
         */
        Dispatcher::register('export:extender', 'all', function ($event) {
            if (!$this->supportTiersManager) { // Can not use DI in constructor due to init races
                $this->supportTiersManager = Di::_()->get('Wire\SupportTiers\Manager');
            }

            $params = $event->getParameters();
            $entity = $params['entity'];

            if (!$entity instanceof PaywallEntityInterface) {
                return; // Not paywallable
            }

            if (!$entity->isPayWall()) {
                return; // Not paywalled
            }

            $export = $event->response() ?: [];
            //$currentUser = Session::getLoggedInUserGuid();

            $wireThreshold = $entity->getWireThreshold();
            if (!$wireThreshold['support_tier']) {
                return; // This is a legacy paywalled post
            }

            $supportTier = $this->supportTiersManager->getByUrn($wireThreshold['support_tier']['urn']);

            if (!$supportTier) {
                return; // Not found?
            }

            // Array Merge so we keep the expires
            $wireThreshold['support_tier'] = array_merge($wireThreshold['support_tier'], $supportTier->export());

            $export['wire_threshold'] = $wireThreshold;

            if ($entity->isPayWallUnlocked()) {
                $export['paywall_unlocked'] = true;
            }

            return $event->setResponse($export);
        });

        /**
         * Verify user meets threshold for interaction with paywalled entity.
         */
        Dispatcher::register('acl:interact', 'all', function ($event) {
            if (!$this->paywallManager) { // Can not use DI in constructor due to init races
                $this->paywallManager = Di::_()->get('Wire\Paywall\Manager');
            }

            $params = $event->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];

            if (!$entity instanceof PaywallEntityInterface) {
                return; // Not paywallable
            }

            if (!$this->paywallManager->setUser($user)->isAllowed($entity)) {
                throw new PaywallUserNotPaid();
            }
        });
    }

    private function extractTeaser($fullText)
    {
        if (!isset($fullText)) {
            return null;
        }

        $teaserText = substr($fullText, 0, 200);

        return $teaserText;
    }

    private function isStatusPost($activity)
    {
        return !$activity->custom_type && !$activity->perma_url && !$activity->remind_object;
    }
}
