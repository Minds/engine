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

        /** @var Repository $repository */
        $repository = Di::_()->get('Boost\Campaigns\Repository');

        $response = $repository->getList([
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
        // TODO: Implement post() method.
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
