<?php
namespace Minds\Core\Security\Block;

use Minds\Common\Repository\Response;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var PsrWrapper */
    protected $cache;

    /** @var Delegates\EventStreamsDelegate */
    protected $eventStreamsDelegate;

    /** @var int */
    const CACHE_TTL = 86400; // 1 day

    public function __construct(Repository $repository = null, PsrWrapper $cache = null, Delegates\EventStreamsDelegate $eventStreamsDelegate = null)
    {
        $this->repository = $repository ?? new Repository();
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
        $this->eventStreamsDelegate = $eventStreamsDelegate ?? new Delegates\EventStreamsDelegate();
    }

    /**
     * Return a list of blocked users
     * @param BlockListOpts $opts
     * @return Response
     */
    public function getList(BlockListOpts $opts): Response
    {
        if ($opts->isUseCache() && $cached = $this->cache->get($this->getCacheKey($opts->getUserGuid()))) {
            /** @var Response */
            $guids = unserialize($cached);

            $response = new Response();
            foreach ($guids as $subjectGuid) {
                $response[] = (new BlockEntry())
                    ->setActorGuid($opts->getUserGuid())
                    ->setSubjectGuid($subjectGuid);
            }
        } else {
            /** @var Response */
            $response = $this->repository->getList($opts);

            if ($opts->isUseCache()) {
                $this->cache->set($this->getCacheKey($opts->getUserGuid()), serialize($response->map(function ($blockEntry) {
                    return $blockEntry->getSubjectGuid();
                })), static::CACHE_TTL);
            }
        }

        return $response;
    }

    /**
     * Adds a new item to the block list
     * @param Block $block
     * @return
     */
    public function add(BlockEntry $block): bool
    {
        /** @var bool */
        $success = $this->repository->add($block);

        if (!$success) {
            return false;
        }

        // Purge the cache
        $this->cache->delete($this->getCacheKey($block->getActorGuid()));

        // Run any cleanup delegates

        // Add to event stream
        $this->eventStreamsDelegate->onAdd($block);

        return true;
    }

    /**
     * Removes a block
     * @param Block $block
     * @return bool
     */
    public function delete(BlockEntry $block): bool
    {
        /** @var bool */
        $success = $this->repository->delete($block);

        if (!$success) {
            return false;
        }

        // Purge the cache
        $this->cache->delete($this->getCacheKey($block->getActorGuid()));

        // Run any cleanup delegates

        // Add to event stream
        $this->eventStreamsDelegate->onDelete($block);

        return true;
    }

    /**
     * Returns if the actor has been blocked on the subject list
     * @param BlockEntry $blockEntry
     * @return bool
     */
    public function isBlocked(BlockEntry $blockEntry): bool
    {
        $opts = new BlockListOpts();
        $opts->setUserGuid($blockEntry->getSubjectGuid());

        /** @var array */
        $blockList = array_map(function ($blockEntry) {
            return $blockEntry->getSubjectGuid(); // Return the subject guid who was blocked
        }, $this->getList($opts)->toArray());

        if (in_array($blockEntry->getActorGuid(), $blockList, true)) {
            return true;
        }

        return false;
    }

    /**
     * The inversion of 'isBlocked(...)'
     * Returns if the actor has blocked the subject
     * @param BlockEntry $blockEntry
     * @return bool
     */
    public function hasBlocked(BlockEntry $blockEntry): bool
    {
        $invertedBlockEntry = new BlockEntry();
        $invertedBlockEntry->setActorGuid($blockEntry->getSubjectGuid())
            ->setSubjectGuid($blockEntry->getActorGuid());

        return $this->isBlocked($invertedBlockEntry);
    }

    //

    /**
     * Returns a cache key
     * @param string $actorGuid
     * @return string
     */
    private function getCacheKey(string $actorGuid): string
    {
        return "acl:block:list:{$actorGuid}";
    }
}
