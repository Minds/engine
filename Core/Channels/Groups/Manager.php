<?php
namespace Minds\Core\Channels\Groups;

use Minds\Common\Repository\Response;
use Minds\Core\Groups\Membership;

/**
 * Channel Groups Manager
 * @package Minds\Core\Channels\Groups
 */
class Manager
{
    /** @var int */
    const MAX_GROUPS = 5000;

    /** @var string */
    protected $userGuid;

    /** @var Repository */
    protected $repository;

    /** @var Membership */
    protected $membership;

    /**
     * Manager constructor.
     * @param $repository
     * @param $membership
     */
    public function __construct(
        $repository = null,
        $membership = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->membership = $membership ?: new Membership();
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
        $opts = array_merge([
            'pageToken' => '',
        ], $opts);

        $guids = $this->membership->getGroupGuidsByMember([
            'user_guid' => $this->userGuid,
            'limit' => static::MAX_GROUPS,
        ]);

        $guids = array_values(array_filter(array_map(function ($guid) {
            return (string) $guid;
        }, $guids)));

        return $this->repository->getList([
            'guids' => $guids,
            'pageToken' => $opts['pageToken'],
        ]);
    }

    /**
     * Gets the public groups count for a channel
     * @return int
     */
    public function count(): int
    {
        $guids = $this->membership->getGroupGuidsByMember([
            'user_guid' => $this->userGuid,
            'limit' => static::MAX_GROUPS,
        ]);

        $guids = array_values(array_filter(array_map(function ($guid) {
            return (string) $guid;
        }, $guids)));

        return $this->repository->count([
            'guids' => $guids,
        ]);
    }
}
