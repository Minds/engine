<?php
namespace Minds\Core\Email\V2\SendLists;

use Minds\Core\Email\Campaigns\EmailCampaign;

interface SendListInterface
{
    /**
     * Sets arguments that the cli has provided
     * @param array $cliOpts
     * @return self
     */
    public function setCliOpts(array $cliOpts = []): self;

    /**
     * @param EmailCampaign $campaign
     * @return self
     */
    public function setCampaign(EmailCampaign $campaign): self;

    /**
     * @param string
     * @return self
     */
    public function setOffset($offset = ''): self;

    /**
     * Returns the list
     * @return iterable
     */
    public function getList(): iterable;
}
