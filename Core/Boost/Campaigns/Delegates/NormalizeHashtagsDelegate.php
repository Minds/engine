<?php
/**
 * NormalizeHashtagsDelegate
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns\Delegates;

use Minds\Core\Boost\Campaigns\Campaign;

class NormalizeHashtagsDelegate
{
    /**
     * @param Campaign $campaign
     * @return Campaign
     */
    public function onCreate(Campaign $campaign)
    {
        $hashtags = $campaign->getHashtags();

        if (is_string($hashtags)) {
            $hashtags = explode(' ', $hashtags);
        }

        $hashtags = array_values(array_unique(array_filter(array_map(function ($hashtag) {
            return preg_replace('/[^a-zA-Z_]/', '', $hashtag);
        }, $hashtags))));

        $campaign->setHashtags($hashtags);

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @param Campaign $oldCampaign
     * @return Campaign
     */
    public function onUpdate(Campaign $campaign, Campaign $oldCampaign)
    {
        return $this->onCreate($campaign);
    }
}
