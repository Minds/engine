<?php
/**
 * Minds Group API
 * Groups listing endpoints
 */
namespace Minds\Controllers\api\v1;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Entities;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Groups\Membership;

class groups implements Interfaces\Api
{
    /**
     * Returns the conversations or conversation
     * @param array $pages
     *
     * API:: /v1/groups/:filter
     */
    public function get($pages)
    {
        $groups = [];
        $user = Session::getLoggedInUser();

        $indexDb = Di::_()->get('Database\Cassandra\Indexes');

        if (!isset($pages[0])) {
            $pages[0] = "featured";
        }

        $opts = [
          'limit' => isset($_GET['limit']) ? (int) $_GET['limit'] : 12,
          'offset' => isset($_GET['offset']) ? $_GET['offset'] : ''
        ];

        switch ($pages[0]) {
            case "member":
                Factory::isLoggedIn();

                $loadNext = 0;

                /** @var Core\Groups\V2\Membership\Manager */
                $manager = Di::_()->get(Core\Groups\V2\Membership\Manager::class);
                $groups = iterator_to_array($manager->getGroups(
                    user: $user,
                    limit: 12, // frontend client is sending 1 incorrectly
                    offset: $opts['offset'],
                    loadNext: $loadNext,
                ));

                $response['load-next'] = $loadNext;
                break;
            case "all":
            default:
                $guids = $indexDb->get('group', $opts);

                if (!$guids) {
                    return Factory::response([]);
                }
        
                $groups = Entities::get(['guids' => $guids]);
                break;
        }


        $response['groups'] = Factory::exportable($groups);
        $response['entities'] = Factory::exportable($groups);

        if (!isset($response['load-next']) && $groups) {
            $response['load-next'] = (string) end($groups)->getGuid();
        }

        return Factory::response($response);
    }

    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
