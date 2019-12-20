<?php

namespace Minds\Controllers\api\v2\boost;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Boost\Network\Campaign;
use Minds\Core\Boost\Network\Manager;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Helpers\Time;
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
        $manager = Di::_()->get('Boost\Network\Manager');
        $manager->setActor(Session::getLoggedInUser());

        $response = $manager->getCampaigns([
            'limit' => $limit,
            'offset' => $offset,
            'guid' => $guid,
            'paused' => 'any'
        ]);

        Factory::response([
            'campaigns' => Factory::exportable($response->toArray()),
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
        $create = true;
        $guid = '';

        if ($pages[0]) {
            $create = false;
            $urn = $pages[0];
            if ($urn) {
                $urn = new Urn($urn);
                $guid = (string) $urn->getNss();
            }
        }

        $campaign = new Campaign();

        if ($create) {
            $entityUrns = $_POST['entity_urns'] ?? [];
            $entityUrn = new Urn($entityUrns[0]);
            $entityGuid = (string) $entityUrn->getNss();

            $campaign
                ->setType($_POST['type'] ?? '')
                ->setEntityGuid($entityGuid)
                ->setCreatedTimestamp(Time::sToMs(time()))
                ->setChecksum($_POST['checksum'] ?? '')
                ->setBidType(Boost::BID_TYPE_TOKENS)
                ->pause();
        } else {
            $campaign->setGuid($guid);
        }

        $campaign
            ->setName(trim($_POST['name'] ?? ''))
            ->setStart((int) ($_POST['start'] ?? 0))
            ->setEnd((int) ($_POST['end'] ?? 0))
            ->setBudget((float) ($_POST['budget'] ?? 0));

        /** @var Manager $manager */
        $manager = Di::_()->get('Boost\Network\Manager');
        $manager->setActor(Session::getLoggedInUser());

        try {
            if ($create) {
                $campaign = $manager->createCampaign($campaign);
            } else {
                $campaign = $manager->updateCampaign($campaign);
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
        $guid = '';

        if (is_null($urn)) {
            Factory::response([
                'status' => 'error',
                'message' => 'Missing URN',
            ]);
            return;
        }

        if ($urn) {
            $urn = new Urn($urn);
            $guid = (string) $urn->getNss();
        }

        $campaign = new Campaign();
        $campaign->setGuid($guid);

        /** @var Manager $manager */
        $manager = Di::_()->get('Boost\Network\Manager');
        $manager->setActor(Session::getLoggedInUser());

        try {
            $campaign = $manager->cancelCampaign($campaign);

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
