<?php
namespace Minds\Core\Security\Block;

use Minds\Core\Security\Block\Repositories\CassandraRepository;
use Minds\Common\Repository\Response;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Security\Block\Repositories\RepositoryInterface;
use Minds\Core\Security\Block\Repositories\VitessRepository;
use Minds\Core\Experiments\Manager as ExperimentsManager;

class Manager
{
    /** @var int */
    const CACHE_TTL = 86400; // 1 day

    // No more than this qty of users may be blocked
    /** @var int */
    const BLOCK_LIMIT = 1000;

    public function __construct(
        protected ?RepositoryInterface $cassandraRepository = null,
        protected ?RepositoryInterface $vitessRepository = null,
        protected ?PsrWrapper $cache = null,
        protected ?Delegates\EventStreamsDelegate $eventStreamsDelegate = null,
        protected ?Delegates\AnalyticsDelegate $analyticsDelegate = null,
        protected ?ExperimentsManager $experimentsManager = null
    ) {
        $this->cassandraRepository ??= new CassandraRepository();
        $this->vitessRepository ??= new VitessRepository();
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
        $this->eventStreamsDelegate ??= new Delegates\EventStreamsDelegate();
        $this->analyticsDelegate ??= new Delegates\AnalyticsDelegate();
        $this->experimentsManager ??= Di::_()->get("Experiments\Manager");
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
            $response = $this->isVitessFeatureActive() ?
                $this->vitessRepository->getList($opts) :
                $this->cassandraRepository->getList($opts);

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
     * @param BlockEntry $block
     * @return
     */
    public function add(BlockEntry $block): bool
    {
        $userGuid = $block->getActorGuid();

        $count = $this->cassandraRepository->countList($userGuid);
        
        // TODO: Remove when deprecating Cassandra
        $limit = static::BLOCK_LIMIT;

        if ($count >= $limit) {
            throw new BlockLimitException();
        }

        /** @var bool */
        $success = $this->cassandraRepository->add($block);

        if ($success && $this->isVitessFeatureActive()) {
            $success = $this->vitessRepository->add($block);
        }

        if (!$success) {
            return false;
        }

        // Purge the cache
        $this->cache->delete($this->getCacheKey($block->getActorGuid()));

        // Run any cleanup delegates

        // Add to event stream
        $this->eventStreamsDelegate->onAdd($block);

        // Add to analytics
        $this->analyticsDelegate->onAdd($block);

        return true;
    }

    /**
     * Removes a block
     * @param BlockEntry $block
     * @return bool
     */
    public function delete(BlockEntry $block): bool
    {
        /** @var bool */
        $success = $this->cassandraRepository->delete($block);

        if ($success && $this->isVitessFeatureActive()) {
            $success = $this->vitessRepository->delete($block);
        }

        if (!$success) {
            return false;
        }

        // Purge the cache
        $this->cache->delete($this->getCacheKey($block->getActorGuid()));

        // Run any cleanup delegates

        // Add to event stream
        $this->eventStreamsDelegate->onDelete($block);

        // Add to analytics
        $this->analyticsDelegate->onDelete($block);

        return true;
    }

    /**
     * Returns if the actor has been blocked on the subject list
     * @param BlockEntry $blockEntry
     * @return bool
     */
    public function isBlocked(BlockEntry $blockEntry): bool
    {
        if (!$blockEntry->getSubjectGuid()) {
            return false;
        }

        return $this->isVitessFeatureActive() ?
            (bool) $this->vitessRepository->get(
                userGuid: $blockEntry->getSubjectGuid(),
                blockedGuid: $blockEntry->getActorGuid()
            ) :
            (bool) $this->cassandraRepository->get(
                userGuid: $blockEntry->getSubjectGuid(),
                blockedGuid: $blockEntry->getActorGuid()
            );
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

    /**
     * Returns a cache key
     * @param string $actorGuid
     * @return string
     */
    private function getCacheKey(string $actorGuid): string
    {
        return "acl:block:list:{$actorGuid}";
    }

    /**
     * Whether vitess repository feature is active.
     * @return boolean Whether vitess repository feature is active.
     */
    private function isVitessFeatureActive(): bool
    {
        return $this->experimentsManager->hasVariation('minds-3747-vitess-blocklist', true);
    }
}
