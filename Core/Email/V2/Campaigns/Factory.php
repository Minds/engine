<?php

namespace Minds\Core\Email\V2\Campaigns;

use Minds\Core\Email\Campaigns\EmailCampaign;

class Factory
{
    /**
     * Build the campaign
     * @param  string $batch
     * @return EmailCampaign
     */
    public static function build($campaign): EmailCampaign
    {
        $campaign = ucfirst($campaign);
        $campaign = "Minds\\Core\\Email\\V2\\Campaigns\\$campaign";
        if (class_exists($campaign)) {
            $class = new $campaign();
            if ($class instanceof EmailCampaign) {
                return $class;
            }
        }
        throw new \Exception("Campaign not found");
    }
}
