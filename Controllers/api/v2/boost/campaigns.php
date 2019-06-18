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
use Minds\Api\Exportable;
use Minds\Common\Urn;
use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\Manager;
use Minds\Core\Boost\Campaigns\Repository;
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

        $response = $manager->getList([
            'owner_guid' => Session::getLoggedinUserGuid(),
            'limit' => $limit,
            'offset' => $offset,
            'guid' => $guid,
        ]);

        return Factory::response([
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
        $campaign = new Campaign();

        $campaign
            ->setOwner(Session::getLoggedInUser())
            ->setName(trim($_POST['name'] ?? ''))
            ->setType($_POST['type'] ?? '')
            ->setEntityUrns($_POST['entity_urns'] ?? [])
            ->setHashtags($_POST['hashtags'] ?? [])
            ->setStart((int) ($_POST['start'] ?? 0))
            ->setEnd((int) ($_POST['end'] ?? 0))
            ->setBudget((float) ($_POST['budget'] ?? 0));

        /** @var Manager $manager */
        $manager = Di::_()->get('Boost\Campaigns\Manager');

        try {
            $campaign = $manager->create($campaign);

            return Factory::response([
                'campaign' => $campaign,
            ]);
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        // TODO: Implement put() method.
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        // TODO: Implement delete() method.
    }
}
