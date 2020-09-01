<?php
namespace Minds\Core\Email\CampaignLogs;

use Minds\Common\Repository\Response;

class Manager
{
    /** @var Repository */
    protected $repository;

    /**
     * @param Repository $repository
     */
    public function __construct($repository = null)
    {
        $this->repository = $repository ?? new Repository();
    }

    /**
     * Adds a campaign to the repository
     * @param CampaignLog $campaignLog
     * @return void
     */
    public function add(CampaignLog $campaignLog): void
    {
        $this->repository->add($campaignLog);
    }

    /**
     * Return a list of campaign logs
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = []): Response
    {
        return $this->repository->getList($opts);
    }
}
