<?php
/**
 * channel
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\pro;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Channel\Manager as ChannelManager;
use Minds\Core\Pro\Manager;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Helpers\Campaigns\Referrals;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\EntitiesBuilder;

class channel implements Interfaces\Api
{
    public $request;

    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws Exception
     */
    public function get($pages)
    {
        $currentUser = Session::getLoggedinUser();

        $channel = Di::_()->get(EntitiesBuilder::class)->getByUserByIndex(strtolower($pages[0]));
        $channel->fullExport = true; //get counts
        $channel->exportCounts = true;

        if (!$channel) {
            return Factory::response([
                'status' => 'error',
                'message' => 'The channel does not exist'
            ]);
        }

        if (
            !$channel->isPro() &&
            $channel->getGuid() !== ($currentUser ? $currentUser->getGuid() : null)
        ) {
            return Factory::response([
                'status' => 'error',
                'message' => 'E_NOT_PRO'
            ]);
        }

        /** @var Manager $manager */
        $manager = Di::_()->get('Pro\Manager');
        $manager->setUser($channel);

        /** @var ChannelManager $manager */
        $channelManager = Di::_()->get('Pro\Channel\Manager');
        $channelManager->setUser($channel);

        switch ($pages[1] ?? '') {
            case 'content':
                return Factory::response([
                    'content' => $channelManager->getAllCategoriesContent(),
                ]);

            default:
                $proSettings = $manager->get();

                $exportedChannel = $channel->export();
                $exportedChannel['pro_settings'] = $proSettings;

                return Factory::response([
                    'channel' => $exportedChannel,
                    'me' => $currentUser ? $currentUser->export() : null,
                ]);
        }
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
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
