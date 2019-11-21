<?php
namespace Minds\Controllers\api\v2\admin\rewards;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Rewards\Withdraw\Manager;
use Minds\Core\Rewards\Withdraw\Request;
use Minds\Entities\User;
use Minds\Interfaces;
use Minds\Api\Factory;

class withdrawals implements Interfaces\Api, Interfaces\ApiAdminPam
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws Exception
     */
    public function get($pages)
    {
        /** @var Manager $manager */
        $manager = Di::_()->get('Rewards\Withdraw\Manager');

        $userGuid = null;

        if ($_GET['user']) {
            $userGuid = (new User(strtolower($_GET['user'])))->guid;
        }

        $status = $_GET['status'] ?? null;

        $opts = [
            'status' => $status,
            'user_guid' => $userGuid,
            'limit' => isset($_GET['limit']) ? (int) $_GET['limit'] : 12,
            'offset' => isset($_GET['offset']) ? $_GET['offset'] : '',
            'hydrate' => true,
            'admin' => true,
        ];

        /** @var Response $withdrawals */
        $withdrawals = $manager->getList($opts);

        return Factory::response([
            'withdrawals' => $withdrawals,
            'load-next' => $withdrawals->getPagingToken(),
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        /** @var Manager $manager */
        $manager = Di::_()->get('Rewards\Withdraw\Manager');

        $request = $manager->get(
            (new Request())
                ->setUserGuid((string) $pages[0] ?? null)
                ->setTimestamp((int) $pages[1] ?? null)
                ->setTx((string) $pages[2] ?? null)
        );

        if (!$request) {
            return Factory::response([
                'status' => 'error',
                'message' => $errorMessage ?? 'Missing request',
            ]);
        }

        try {
            $success = $manager->approve($request);
        } catch (Exception $exception) {
            $success = false;
            $errorMessage = $exception->getMessage();
        }

        if (!$success) {
            return Factory::response([
                'status' => 'error',
                'message' => $errorMessage ?? 'Cannot approve request',
            ]);
        }

        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        /** @var Manager $manager */
        $manager = Di::_()->get('Rewards\Withdraw\Manager');

        $request = $manager->get(
            (new Request())
                ->setUserGuid((string) $pages[0] ?? null)
                ->setTimestamp((int) $pages[1] ?? null)
                ->setTx((string) $pages[2] ?? null)
        );

        if (!$request) {
            return Factory::response([
                'status' => 'error',
                'message' => $errorMessage ?? 'Missing request',
            ]);
        }

        try {
            $success = $manager->reject($request);
        } catch (Exception $exception) {
            $success = false;
            $errorMessage = $exception->getMessage();
        }

        if (!$success) {
            return Factory::response([
                'status' => 'error',
                'message' => $errorMessage ?? 'Cannot reject request',
            ]);
        }

        return Factory::response([]);
    }
}
