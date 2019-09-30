<?php

namespace Minds\Controllers\api\v2\subscriptions;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Subscriptions\Requests\SubscriptionRequest;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Interfaces;

/**
 * Outgoing subscritions
 */
class outgoing implements Interfaces\Api
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
     * @param string $publisherGuid
     * @return void
     */
    private function getSingle(string $publisherGuid): bool
    {
        // Return a single request
        $manager = Di::_()->get('Subscriptions\Requests\Manager');
        
        // Construct URN on the fly
        $urn = "urn:subscription-request:" . implode('-', [ $publisherGuid, Session::getLoggedInUserGuid() ]);
        
        $request = $manager->get($urn);

        if (!$request || $request->getSubscriberGuid() != Session::getLoggedInUserGuid()) {
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
        return Factory::response([
            'requests' => [],
            'next' => null,
        ]);
    }

    public function post($pages)
    {
        // Void
        return Factory::response([]);
    }

    public function put($pages)
    {
        // Make a subscription request
        $manager = Di::_()->get('Subscriptions\Requests\Manager');

        $request = new SubscriptionRequest();
        $request->setPublisherGuid($pages[0])
            ->setSubscriberGuid(Session::getLoggedInUserGuid());

        try {
            $manager->add($request);
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
        // Delete a subscription request
        return Factory::response([]);
    }
}
