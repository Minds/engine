<?php

namespace Minds\Core\Feeds\Scheduled;

class Manager
{
    /** @var Repository */
    protected $repository;

    public function __construct(
        $repository = null
    ) {
        $this->repository = $repository ?: new Repository;
    }

    /**
     * @param array $opts
     * @return int
     */
    public function getScheduledCount(array $opts = [])
    {
        return $this->repository->getScheduledCount($opts) ;
    }
}
