<?php
/**
 * NormalizeHashtagsDelegate
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns\Delegates;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;

class NormalizeHashtagsDelegate
{
    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
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

        if (count($hashtags) > 5) {
            throw new CampaignException('Campaigns should have 5 hashtags or less');
        }

        $campaign
            ->setHashtags($hashtags);

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     */
    public function onUpdate(Campaign $campaign, Campaign $campaignRef)
    {
        $campaign
            ->setHashtags($campaignRef->getHashtags());

        return $this->onCreate($campaign);
    }
}
