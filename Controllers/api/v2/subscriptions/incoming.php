<?php

namespace Minds\Controllers\api\v2\subscriptions;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Subscriptions\Requests\Manager;
use Minds\Interfaces;

/**
 * Incoming subscriptions
 */
class incoming implements Interfaces\Api
{
    public function get($pages): bool
    {
        if (isset($pages[0])) {
            return $this->getSingle($pages[0]);
        } else {
            return $this->getList();
        }
    }

    /**
     * Return a single request
     * @param string $subscriberGuid
     * @return void
     */
    private function getSingle(string $subscriberGuid): bool
    {
        // Return a single request
        /** @var Manager $manager */
        $manager = Di::_()->get('Subscriptions\Requests\Manager');

        // Construct URN on the fly
        $urn = "urn:subscription-request:" . implode('-', [ Session::getLoggedInUserGuid(), $subscriberGuid ]);

        $request = $manager->get($urn);

        if (!$request || $request->getPublisherGuid() != Session::getLoggedInUserGuid()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Not found',
            ]);
        }

        return Factory::response([
            'request' => $request->export(),
        ]);
    }

    /**
     * Return a list of subscription requests
     * @return bool
     */
    private function getList(): bool
    {
        // Return a list of subscription requests
        /** @var Manager $manager */
        $manager = Di::_()->get('Subscriptions\Requests\Manager');

        $requests = $manager->getIncomingList(Session::getLoggedInUserGuid(), []);

        return Factory::response([
            'requests' => Factory::exportable($requests),
            'next' => $requests->getPagingToken(),
        ]);
    }

    public function post($pages)
    {
        // Void
        return Factory::response([]);
    }

    public function put($pages)
    {
        // Accept / Deny
        /** @var Manager $manager */
        $manager = Di::_()->get('Subscriptions\Requests\Manager');

        // Construct URN on the fly
        $subscriberGuid = $pages[0];
        $urn = "urn:subscription-request:" . implode('-', [ Session::getLoggedInUserGuid(), $subscriberGuid ]);

        $request = $manager->get($urn);

        if (!$request || $request->getPublisherGuid() != Session::getLoggedInUserGuid()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Not found',
            ]);
        }

        try {
            switch ($pages[1]) {
                case "accept":
                    $manager->accept($request);
                    break;
                case "decline":
                    $manager->decline($request);
                    break;
            }
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return Factory::response([]);
    }

    public function delete($pages)
    {
        // Void
        return Factory::response([]);
    }
}
