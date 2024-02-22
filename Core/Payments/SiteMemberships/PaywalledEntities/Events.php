<?php

namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\PaywalledEntityGatekeeperService;
use Minds\Core\Session;
use Minds\Entities\Activity;

class Events
{
    public function __construct(
        private ?PaywalledEntityGatekeeperService $paywalledEntityGatekeeperService = null
    ) {
    }

    public function register()
    {
        Dispatcher::register('export:extender', 'all', [$this, 'onExportExtend']);
    }

    /*
    * Removes important export fields if marked as paywall
    */
    public function onExportExtend(Event $event): void
    {
        $params = $event->getParameters();
        $activity = $params['entity'];

        $export = $event->response() ?: [];

        if (!$activity instanceof Activity) {
            return;
        }

        if (!$activity->hasSiteMembership()) {
            return;
        }

        $loggedInUser = Session::getLoggedinUser();

        // Determine if a user has permission to view this site membership
        // paywalled post
        $export['site_membership_unlocked'] = $this->getPaywalledEntityGatekeeperService()->canAccess($activity, $loggedInUser);

        if ($export['site_membership_unlocked'] === true) {
            $event->setResponse($export);
            return;
        }

        if ($activity->hasAttachments()) {
            // Image post
        } elseif ($activity->getCustomType() === 'video') {
            // Video post
        } elseif ($activity->getPermaURL()) {
            // Rich embed

            // Remove the message
            $export['message'] = '';
            $export['perma_url'] = '';
        } else {
            // Text only

            // Remove the message
            $export['message'] = '';
        }

        if ($activity->paywall_thumbnail) {
            $export['paywall_thumbnail'] = [
                'width' => (int) $activity->paywall_thumbnail['width'] ??= 0,
                'height' => (int) $activity->paywall_thumbnail['height'] ??= 0,
                'blurhash' => $activity->paywall_thumbnail['blurhash'],
            ];
        } else {
            $export['paywall_thumbnail'] = null;
        }

        $event->setResponse($export);
    }

    private function getPaywalledEntityGatekeeperService(): PaywalledEntityGatekeeperService
    {
        return $this->paywalledEntityGatekeeperService ?? Di::_()->get(PaywalledEntityGatekeeperService::class);
    }
}
