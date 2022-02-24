<?php


namespace Minds\Core\Email\V2\SendLists;

use Minds\Core\Email\Campaigns\EmailCampaign;

abstract class AbstractSendList implements SendListInterface
{
    /** @var CampaignInterface */
    protected $campaign;
   
    /** @var string */
    public $offset = "";

    /**
     * Sets arguments that the cli has provided
     * @param array $cliOpts
     * @return self
     */
    public function setCliOpts(array $cliOpts = []): SendListInterface
    {
        // Doesn't do anything
        return $this;
    }

    /**
     * @param EmailCampaign $campaign
     * @return self
     */
    public function setCampaign(EmailCampaign $campaign): SendListInterface
    {
        $this->campaign = $campaign;
        return $this;
    }


    public function setOffset($offset = ''): SendListInterface
    {
        $this->offset = $offset;
        return $this;
    }
}
