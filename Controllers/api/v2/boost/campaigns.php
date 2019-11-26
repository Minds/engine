<?php
/**
 * Boost Campaigns
 *
 * @version 2
 * @author emi
 *
 */

namespace Minds\Controllers\api\v2\boost;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\Manager;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Api\Factory;

class campaigns implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws Exception
     */
    public function get($pages)
    {
        $limit = $_GET['limit'] ?? 12;
        $offset = $_GET['offset'] ?? '';
        $urn = $pages[0] ?? null;

        if ($limit > 50 || $limit < 0) {
            $limit = 12;
        }

        $guid = '';

        if ($urn) {
            $limit = 1;
            $offset = '';

            $urn = new Urn($urn);
            $guid = (string) $urn->getNss();
        }

        /** @var Manager $manager */
        $manager = Di::_()->get('Boost\Campaigns\Manager');
        $manager->setActor(Session::getLoggedInUser());

        $response = $manager->getCampaigns([
            'owner_guid' => Session::getLoggedinUserGuid(),
            'limit' => $limit,
            'offset' => $offset,
            'guid' => $guid,
        ])->filter(function (Campaign $campaign) {
            return $campaign->getOwnerGuid() == Session::getLoggedinUserGuid();
        });

        Factory::response([
            'campaigns' => $response,
            'load-next' => $response->getPagingToken(),
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        $isEditing = false;
        $urn = null;

        if ($pages[0]) {
            $isEditing = true;
            $urn = $pages[0];
        }

        $campaign = new Campaign();

        if (!$isEditing) {
            $campaign
                ->setType($_POST['type'] ?? '')
                ->setEntityUrns($_POST['entity_urns'] ?? [])
                ->setBudgetType($_POST['budget_type'] ?? '')
                ->setChecksum($_POST['checksum'] ?? '');
        } else {
            $campaign
                ->setUrn($urn);
        }

        $campaign
            ->setName(trim($_POST['name'] ?? ''))
            ->setHashtags($_POST['hashtags'] ?? [])
            ->setStart((int) ($_POST['start'] ?? 0))
            ->setEnd((int) ($_POST['end'] ?? 0))
            ->setBudget((float) ($_POST['budget'] ?? 0));

        /** @var Manager $manager */
        $manager = Di::_()->get('Boost\Campaigns\Manager');
        $manager->setActor(Session::getLoggedInUser());

        try {
            if (!$isEditing) {
                $campaign = $manager->createCampaign($campaign, $_POST['payment'] ?? null);
            } else {
                $campaign = $manager->updateCampaign($campaign, $_POST['payment'] ?? null);
            }

            Factory::response([
                'campaign' => $campaign,
            ]);
        } catch (\Exception $e) {
            Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     */
    public function put($pages)
    {
        Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     */
    public function delete($pages)
    {
        $urn = $pages[0] ?? null;

        if (!$urn[0]) {
            Factory::response([
                'status' => 'error',
                'message' => 'Missing URN',
            ]);
            return;
        }

        $campaign = new Campaign();

        $campaign
            ->setUrn($urn);

        /** @var Manager $manager */
        $manager = Di::_()->get('Boost\Campaigns\Manager');
        $manager->setActor(Session::getLoggedInUser());

        try {
            $campaign = $manager->cancel($campaign);

            Factory::response([
                'campaign' => $campaign,
            ]);
        } catch (\Exception $e) {
            Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
