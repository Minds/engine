<?php
/**
 * Minds Admin: Verify
 *
 * @version 1
 * @author Mark Harding
 *
 */
namespace Minds\Controllers\api\v1\admin;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Helpers;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Entities\Actions\Save;

class verify implements Interfaces\Api, Interfaces\ApiAdminPam
{
    protected Save $save;

    public function __construct()
    {
        $this->save = new Save();
    }

    /**
     *
     */
    public function get($pages)
    {
        /**
         * This needs its own class.. done for speed atm
         */
        $db = new Core\Data\Call('entities_by_time');

        $limit = isset($_GET['limit']) ? $_GET['limit'] : 24;
        $offset = isset($_GET['offset']) ? $_GET['offset'] : '';

        $requests = $db->getRow('verify:requests', [ 'limit' => $limit, 'offset' => $offset ]);

        $response = [];
        foreach ($requests as $request) {
            $payload = json_decode($request, true);
            $user = Entities\Factory::build($payload['guid']);
            if (!$user) {
                continue; // User deleted
            }
            $payload['user'] = $user->export();
            $response['requests'][] = $payload;
        }

        if ($response['requests']) {
            $response['load-next'] = end(array_keys($requests));
        }

        return Factory::response($response);
    }

    /**
     * @param array $pages
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Verify a user
     * @param array $pages
     */
    public function put($pages)
    {
        if (!isset($pages[0]) || !$pages[0]) {
            return [
                'error' => true
            ];
        }

        $db = new Core\Data\Call('entities_by_time');

        $user = new Entities\User($pages[0]);

        if (!$user || !$user->guid) {
            return [
                'error' => true
            ];
        }

        $user->verified = true;

        $this->save->setEntity($user)->withMutatedAttributes(['verified'])->save();

        \cache_entity($user);

        $db->removeAttributes('verify:requests', [ $user->guid ]);

        return Factory::response([
            'done' => true
        ]);
    }

    /**
     * Unverify a user
     * @param array $pages
     */
    public function delete($pages)
    {
        if (!isset($pages[0]) || !$pages[0]) {
            return [
                'error' => true
            ];
        }

        $db = new Core\Data\Call('entities_by_time');

        $user = new Entities\User($pages[0]);

        if (!$user || !$user->guid) {
            return [
                'error' => true
            ];
        }

        $user->verified = false;

        $this->save->setEntity($user)->withMutatedAttributes(['verified'])->save();

        \cache_entity($user);

        $db->removeAttributes('verify:requests', [ $user->guid ]);

        return Factory::response([
            'done' => true
        ]);
    }
}
