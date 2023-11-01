<?php

namespace Minds\Core\Wire\Paywall;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Events\Dispatcher;
use Minds\Entities\Activity;
use Minds\Core\Experiments\Manager as ExperimentsManager;

class Events
{
    /** @var SupportTier\Manager */
    private $supportTiersManager;

    /** @var Paywall\Manager */
    private $paywallManager;

    public function __construct($supportTiersManager = null, $paywallManager = null, private ?ExperimentsManager $experimentsManager = null)
    {
        $this->supportTiersManager = $supportTiersManager;
        $this->paywallManager = $paywallManager;
    }

    public function register()
    {
        /*
         * Removes important export fields if marked as paywall
         */
        Dispatcher::register('export:extender', 'all', function ($event) {
            $params = $event->getParameters();
            $activity = $params['entity'];

            $export = $event->response() ?: [];
            $currentUser = Session::getLoggedInUserGuid();
            $currentUserEntity = Session::getLoggedInUser();

            $dirty = false;

            if (!$activity instanceof PaywallEntityInterface) {
                return;
            }

            $export['paywall'] = $activity->isPayWall();

            if ($activity->isPayWall() &&
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

                $paywallContextExperimentOn = $this->getExperimentsManager()
                    ->setUser(Session::getLoggedInUser())
                    ->isOn('minds-3857-paywall-context');

                // Only export teaser for non-status posts and users in the experiment
                if (!$this->isStatusPost($activity) && $paywallContextExperimentOn) {
                    $export['message'] = $this->extractTeaser($activity->message);
                } else {
                    $export['message'] = null;
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

                if ($isAllowed) {
                    return $event->setResponse(true);
                }
            } catch (\Exception $e) {
            }

            return $event->setResponse(false);
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
        return !$activity->custom_type && !$activity->perma_url && !$activity->remind_object && (!($activity instanceof Activity && $activity->hasAttachments()));
    }

    private function getExperimentsManager(): ExperimentsManager
    {
        return $this->experimentsManager ??= Di::_()->get('Experiments\Manager');
    }
}
