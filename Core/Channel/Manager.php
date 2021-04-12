<?php
namespace Minds\Core\Channel;

use Minds\Core\Di\Di;
use Minds\Entities\Channel;
use Minds\Exceptions\UserErrorException;
use Minds\Core\EntitiesBuilder;
use Minds\Api\Factory;
use Minds\Helpers;

/**
 * Channel Manager
 * @package Minds\Core\Channel
 */
class Manager
{
    /**
     * Get the channel
     * @param string channel username or guid
     * @return User
     */
    public function getChannel(string $channel)
    {
        if (!is_numeric($channel)) {
            $channel = strtolower($channel);
        }

        $channel = new Channel($channel);

        // Flush the cache when viewing a channel page
        $channelsManager = Di::_()->get('Channels\Manager');
        $channelsManager->flushCache($channel);

        if ($this->validate($channel) === NULL) {
            return;
        };

        Di::_()->get('Referrals\Cookie')
            ->setEntity($channel)
            ->create();

        $channel->fullExport = true; //get counts
        $channel->exportCounts = true;

        $response = [
            'channel' => Factory::exportable([$channel])[0],
            'require_login' => $channel->requireLogin(), 
        ];
        
        return $response;
    }

    private function validate(Channel $channel)
    {
        $isAdmin = $channel->isAdmin;
        if (!$channel->username ||
            (Helpers\Flags::shouldFail($channel) && !$isAdmin)
        ) {
            return Factory::response([
                'status'=>'error',
                'message'=>'Sorry, this user could not be found',
                'type'=>'ChannelNotFoundException',
            ]);
        }
        if ($channel->enabled != "yes" && !$isAdmin) {
            return Factory::response([
                'status'=>'error',
                'message'=>'Sorry, this user is disabled',
                'type'=>'ChannelDisabledException',
            ]);
        }

        if ($channel->banned == 'yes' && !$isAdmin) {
            return Factory::response([
                'status'=>'error',
                'message'=>'This user has been banned',
                'type'=>'ChannelBannedException',
            ]);
        }

        return true;
    }
}
