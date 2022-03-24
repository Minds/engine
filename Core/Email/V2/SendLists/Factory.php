<?php

namespace Minds\Core\Email\V2\SendLists;

use Minds\Core\Email\Campaigns\EmailCampaign;

class Factory
{
    /**
     * Build the campaign
     * @param  string $batch
     * @return EmailCampaign
     */
    public static function build($campaign): SendListInterface
    {
        $campaign = ucfirst($campaign);
        $campaign = "Minds\\Core\\Email\\V2\\SendLists\\$campaign";
        if (class_exists($campaign)) {
            $class = new $campaign();
            if ($class instanceof SendListInterface) {
                return $class;
            }
        }
        throw new \Exception("Campaign not found");
    }
}
