<?php
namespace Minds\Core\Channels\Groups;

use Minds\Common\Repository\Response;

/**
 * Channel Groups Manager
 * @package Minds\Core\Channels\Groups
 */
class Manager
{
    /** @var string */
    protected $userGuid;

    /** @var Repository */
    protected $repository;

    /**
     * Manager constructor.
     * @param $repository
     */
    public function __construct(
        $repository = null
    ) {
        $this->repository = $repository ?: new Repository();
    }

    /**
     * @param string $userGuid
     * @return Manager
     */
    public function setUserGuid(string $userGuid): Manager
    {
        $this->userGuid = $userGuid;
        return $this;
    }

    /**
     * Gets the public groups for a channel
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = []): Response
    {
        return $this->repository->getList([
            'user_guid' => $this->userGuid,
            'query' => $opts['query'] ?? '',
            'pageToken' => $opts['pageToken'] ?? '',
        ]);
    }

    /**
     * Gets the public groups count for a channel
     * @return int
     */
    public function count(): int
    {
        return $this->repository->count([
            'user_guid' => $this->userGuid,
        ]);
    }
}
