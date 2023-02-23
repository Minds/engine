<?php
namespace Minds\Core\Security\Block\Repositories;

use Minds\Common\Repository\Response;
use Minds\Core\Security\Block\BlockEntry;
use Minds\Core\Security\Block\BlockListOpts;

interface RepositoryInterface
{
    /**
     * Adds a block to the database
     * @param Block $block
     * @return bool
     */
    public function add(BlockEntry $block): bool;

    /**
     * Removes a block to the database
     * @param Block $block
     * @return bool
     */
    public function delete(BlockEntry $block): bool;

    /**
     * Return a list of blocked users
     * @return Response
     */
    public function getList(BlockListOpts $opts): Response;

    /**
     * Count blocks
     * @param string $userGuid
     * @return int
     */
    public function countList(string $userGuid): int;

    /**
     * Get a single block entry
     * @param string $userGuid - user guid to get entry for.
     * @param string $blockedGuid - guid of blocked user.
     * @return BlockEntry
     */
    public function get(string $userGuid, string $blockedGuid): ?BlockEntry;
}
