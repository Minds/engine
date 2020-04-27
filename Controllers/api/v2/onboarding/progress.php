<?php

namespace Minds\Controllers\api\v2\onboarding;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Onboarding\Manager;
use Minds\Core\Session;
use Minds\Interfaces;

class progress implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws \Exception
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        /** @var Manager */
        $manager = new Manager();
        $manager->setUser(Session::getLoggedInUser());

        /** @var \Minds\Core\Features\Manager $manager */
        $featuresManager = Di::_()->get('Features\Manager');

        $allItems = $manager->getAllItems();
        $completedItems = $manager->getCompletedItems();

        if ($featuresManager->has('ux-2020')) {
            $showOnboarding = !$manager->wasOnboardingShown();
        } else {
            $showOnboarding = !$manager->wasOnboardingShown() && count($allItems) > count($completedItems);
        }

        return Factory::response([
            'show_onboarding' => $showOnboarding,
            'all_items' => $allItems,
            'completed_items' => $completedItems,
            'creator_frequency' => $manager->getCreatorFrequency(),
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     * @throws \Exception
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
