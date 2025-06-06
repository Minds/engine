<?php

/**
 * Minds Comments Manager
 *
 * @author emi
 */

namespace Minds\Core\Comments;

use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Luid;
use Minds\Core\Security\ACL;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Exceptions\BlockedUserException;
use Minds\Exceptions\InvalidLuidException;
use Minds\Common\Repository\Response;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\EventStreams\UndeliveredEventException;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RbacGatekeeperService;

class Manager
{
    // timespan to check rate limit.
    const RATE_LIMIT_TIMESPAN = 5;

    // max amount of occurrence in timespan.
    const RATE_LIMIT_MAX = 1;

    /** @var Repository */
    protected $repository;

    /** @var Legacy\Repository */
    protected $legacyRepository;

    /** @var ACL */
    protected $acl;

    /** @var Delegates\Metrics */
    protected $metrics;

    /** @var Delegates\CreateEventDispatcher */
    protected $createEventDispatcher;

    /** @var Delegates\CountCache */
    protected $countCache;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Security\Spam */
    protected $spam;

    /** @var KeyValueLimiter */
    protected $kvLimiter;

    /** @var Comment[] */
    protected $tmpCacheByUrn = [];

    /**
     * Manager constructor.
     * @param Repository|null $repository
     */
    public function __construct(
        $repository = null,
        $legacyRepository = null,
        $acl = null,
        $metrics = null,
        $createEventDispatcher = null,
        $countCache = null,
        $entitiesBuilder = null,
        $spam = null,
        $kvLimiter = null,
        protected ?EventsDispatcher $eventsDispatcher = null,
        protected ?RelationalRepository $relationalRepository = null,
        protected ?RbacGatekeeperService $rbacGatekeeperService = null,
        protected ?Logger $logger = null,
    ) {
        $this->repository = $repository ?: new Repository();
        $this->legacyRepository = $legacyRepository ?: new Legacy\Repository();
        $this->acl = $acl ?: ACL::_();
        $this->metrics = $metrics ?: new Delegates\Metrics();
        $this->createEventDispatcher = $createEventDispatcher ?: new Delegates\CreateEventDispatcher();
        $this->countCache = $countCache ?: new Delegates\CountCache();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->spam = $spam ?: Di::_()->get('Security\Spam');
        $this->kvLimiter = $kvLimiter ?? Di::_()->get("Security\RateLimits\KeyValueLimiter");
        $this->eventsDispatcher ??= Di::_()->get('EventsDispatcher');
        $this->relationalRepository ??= Di::_()->get(RelationalRepository::class);
        $this->rbacGatekeeperService ??= Di::_()->get(RbacGatekeeperService::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    public function get($entity_guid, $parent_path, $guid)
    {
        return $this->repository->get($entity_guid, $parent_path, $guid);
    }

    public function getList($opts = [])
    {
        $opts = array_merge([
            'entity_guid' => null,
            'parent_guid' => null,
            'guid' => null,
            'limit' => null,
            'offset' => null,
            'descending' => true,
            'is_focused' => false,
            'exclude_pinned' => false,
            'only_pinned' => false,
        ], $opts);

        if ($this->legacyRepository->isLegacy($opts['entity_guid'])) {
            return $this->legacyRepository->getList($opts);
        }

        $response = $this->repository->getList($opts);
        $response = $this->filterResponse($response);

        if ($opts['is_focused'] === true && $opts['offset']) {
            $count = count($response);
            $diff = $opts['limit'] - $count;
            if ($diff <= 0) {
                return $response; // no need to load anything else
            }
            $earlier = $this->repository->getList(array_merge($opts, [
                'limit' => $diff,
                'descending' => !$opts['descending'],
                'include_offset' => false,
            ]));

            $newResponse = $this->filterResponse($earlier->reverse());
            foreach ($response as $comment) {
                $newResponse[] = $comment;
            }

            $newResponse->setPagingToken($response->getPagingToken());
            $newResponse->setLastPage($response->isLastPage());
            return $newResponse;
        }

        return $response;
    }

    protected function filterResponse(Response $response): Response
    {
        $filtered = new Response();
        $filtered->setPagingToken($response->getPagingToken());
        $filtered->setLastPage($response->isLastPage());
        foreach ($response as $comment) {
            try {
                $entity = $this->entitiesBuilder->single($comment->getEntityGuid());
                $commentOwner = $this->entitiesBuilder->single($comment->getOwnerGuid());

                if (!$entity) {
                    error_log("{$comment->getEntityGuid()} found comment but entity not found");
                    continue;
                }
                
                if (!$commentOwner) {
                    error_log("{$comment->getEntityGuid()} found comment but owner {$comment->getOwnerGuid()} not found");
                    continue;
                }

                if (!$this->acl->interact($entity, $commentOwner)) {
                    error_log("{$comment->getEntityGuid()} found comment that entity owner can not interact with. Consider deleting.");
                    // $this->delete($comment, [ 'force' => true ]);
                    continue;
                }

                if (!$this->acl->read($comment)) {
                    error_log("{$comment->getEntityGuid()} found comment we can't read");
                    continue;
                }
                $filtered[] = $comment;
            } catch (\Exception $e) {
                error_log("{$comment->getEntityGuid()} exception reading comment {$e->getMessage()}");
            }
        }
        return $filtered;
    }

    /**
     * Adds a comment and triggers creation events
     * @param Comment $comment
     * @return bool
     * @throws BlockedUserException
     * @throws \Minds\Exceptions\StopEventException
     * @throws \Minds\Core\Router\Exceptions\UnverifiedEmailException
     * @throws \Minds\Core\Wire\Paywall\PaywallUserNotPaid
     */
    public function add(Comment $comment, bool $rateLimit = true)
    {
        // Check RBAC
        $this->rbacGatekeeperService->isAllowed(PermissionsEnum::CAN_COMMENT);

        $entity = $this->entitiesBuilder->single($comment->getEntityGuid());

        /** @var User */
        $owner = $this->entitiesBuilder->single($comment->getOwnerGuid());

        //if (!$this->acl->interact($entity, $owner, "comment")) {
        //    throw new \Exception();
        //}

        // Can throw RateLimitException.
        if ($rateLimit) {
            $this->kvLimiter
                ->setKey('comment-limit')
                ->setValue($owner->getGuid())
                ->setSeconds(self::RATE_LIMIT_TIMESPAN)
                ->setMax(self::RATE_LIMIT_MAX)
                ->checkAndIncrement();
        }

        $this->spam->check($comment);
        $this->spam->check($entity);

        if (
            !$comment->getOwnerGuid() ||
            !$this->acl->interact($entity, $owner, 'comment')
        ) {
            throw new BlockedUserException();
        }

        if (!$this->canInteractWithParentTree($comment)) {
            throw new BlockedUserException();
        }

        try {
            if ($this->legacyRepository->isFallbackEnabled()) {
                $this->legacyRepository->add($comment, Repository::$allowedEntityAttributes, false);
            }
        } catch (\Exception $e) {
            error_log("[Comments\Repository::add/legacy] {$e->getMessage()} > " . get_class($e));
        }

        $success = $this->repository->add($comment);

        if ($success) {
            $this->metrics->push($comment);

            $this->createEventDispatcher->dispatch($comment);

            $this->countCache->destroy($comment);

            try {
                $this->eventsDispatcher->trigger('entities-ops', 'create', [
                    'entityUrn' => $comment->getUrn(),
                ]);
            } catch (UndeliveredEventException $e) {
                // Unable to create the comment event, delete
                $this->repository->delete($comment);
                // Rethrow
                throw $e;
            }

            try {
                (new \Minds\Core\Sockets\Events())
                ->setRoom("comments:{$comment->getEntityGuid()}:{$comment->getParentPath()}")
                ->emit(
                    'comment',
                    (string) $comment->getEntityGuid(),
                    (string) $comment->getOwnerGuid(),
                    (string) $comment->getGuid()
                );
                // Emit to parent
                (new \Minds\Core\Sockets\Events())
                ->setRoom("comments:{$comment->getEntityGuid()}:{$comment->getParentPath()}")
                ->emit(
                    'reply',
                    (string) ($comment->getParentGuidL2() ?: $comment->getParentGuidL1())
                );
            } catch (\Exception $e) {
                var_dump($e);
            }
        }

        return $success;
    }

    /**
     * Updates a comment and triggers updating events
     * @param Comment $comment
     * @return bool
     */
    public function update(Comment $comment)
    {
        $this->spam->check($comment);
        if ($entity = $this->entitiesBuilder->single($comment->getEntityGuid())) {
            $this->spam->check($entity);
        }

        if ($this->legacyRepository->isFallbackEnabled()) {
            $this->legacyRepository->add($comment, $comment->getDirtyAttributes(), true);
        }

        $updated = $this->repository->update($comment, $comment->getDirtyAttributes());

        if ($updated) {
            $this->eventsDispatcher->trigger('entities-ops', 'update', [
                'entityUrn' => $comment->getUrn(),
            ]);
        }

        return $updated;
    }


    /**
     * Restores a comment that was deleted from the database
     * @param Comment $comment
     * @return bool
     * @throws BlockedUserException
     */
    public function restore(Comment $comment)
    {
        $entity = $this->entitiesBuilder->single($comment->getEntityGuid());

        /** @var User */
        $owner = $this->entitiesBuilder->single($comment->getOwnerGuid());

        if (
            !$comment->getOwnerGuid() ||
            !$this->acl->interact($entity, $owner)
        ) {
            throw new BlockedUserException();
        }

        try {
            if ($this->legacyRepository->isFallbackEnabled()) {
                $this->legacyRepository->add($comment, Repository::$allowedEntityAttributes, false);
            }
        } catch (\Exception $e) {
            error_log("[Comments\Repository::restore/legacy] {$e->getMessage()} > " . get_class($e));
        }

        $success = $this->repository->add($comment);

        if ($success) {
            $this->countCache->destroy($comment);
        }

        return $success;
    }

    /**
     * Deletes a comment and triggers deletion events
     * @param Comment $comment
     * @param array $opts
     * @return bool
     */
    public function delete(Comment $comment, $opts = [])
    {
        $opts = array_merge([
            'force' => false,
        ], $opts);

        if (!$this->acl->write($comment) && !$opts['force']) {
            return false; //TODO throw exception
        }

        $success = $this->repository->delete($comment);

        if ($success) {
            $this->countCache->destroy($comment);

            $this->eventsDispatcher->trigger('entities-ops', 'delete', [
                'entityUrn' => $comment->getUrn(),
                'entity' => $comment,
            ]);
        }

        return $success;
    }

    /**
     * Get a comment using its LUID. Fallbacks to legacy GUID lookup, if needed.
     * @param Luid|string $luid
     * @return Comment|null
     * @throws \Exception
     */
    public function getByLuid($luid)
    {
        try {
            $luid = new Luid($luid);

            if ($this->legacyRepository->isLegacy($luid->getEntityGuid())) {
                return $this->legacyRepository->getByGuid($luid->getGuid());
            }

            return $this->repository->get($luid->getEntityGuid(), $luid->getPartitionPath(), $luid->getGuid());
        } catch (InvalidLuidException $e) {
            // Fallback to old GUIDs
            if (is_numeric($luid) && strlen($luid) >= 18) {
                return $this->legacyRepository->getByGuid($luid);
            }
        }

        return null;
    }

    /**
     * @param string|Urn $urn
     * @param bool $skipCache
     * @return Comment|null
     * @throws \Exception
     */
    public function getByUrn($urn, $skipCache = false)
    {
        if (is_string($urn)) {
            $urn = new Urn($urn);
        }
        $components = explode(':', $urn->getNss());

        if (count($components) === 1) {
            return $this->getByGuid($components[0]);
        }

        if (count($components) !== 5) {
            error_log("[CommentsManager]: Invalid Comment URN ($components)");
            return null;
        }

        // Prevent grabbing the same comment multiple times per request (eg. notifications)
        if (isset($this->tmpCacheByUrn[(string) $urn]) && $this->tmpCacheByUrn[(string) $urn]) {
            return $this->tmpCacheByUrn[(string) $urn];
        }

        $entityGuid = $components[0];
        $parentPath = "{$components[1]}:{$components[2]}:{$components[3]}";
        $guid = $components[4];

        if ($this->legacyRepository->isLegacy($entityGuid)) {
            return $this->legacyRepository->getByGuid($guid);
        }

        $comment = $this->repository->get($entityGuid, $parentPath, $guid);

        // Populate the cache only if we're not skipping it
        if (!$skipCache) {
            $this->tmpCacheByUrn[(string) $urn] = $comment;
        }

        return $comment;
    }

    /**
     * Currently only  works with comments since May 2023
     */
    public function getByGuid(int $guid): ?Comment
    {
        return $this->relationalRepository->getByGuid($guid);
    }

    /**
     * Counts comments on an entity
     * @param int $entity_guid
     * @param int $parent_guid
     * @return int
     */
    public function count($entity_guid, $parent_guid = null, $countOwners = false)
    {
        try {
            if ($countOwners) {
                return $this->repository->countOwners($entity_guid);
            }
            $count = $this->repository->count($entity_guid, $parent_guid);
        } catch (\Exception $e) {
            error_log('Comments\Manager::count ' . get_class($e) . ':' . $e->getMessage());
            $count = 0;
        }

        return $count;
    }

    /**
     * True/False if the comment creator can interact with the parent tree
     * @param Comment $comment
     * @return bool
     */
    private function canInteractWithParentTree(Comment $comment): bool
    {
        /** @var User */
        $owner = $this->entitiesBuilder->single($comment->getOwnerGuid());

        if ($comment->getParentGuidL2()) {
            $parent = $this->get($comment->getEntityGuid(), $comment->getParentPath(), $comment->getParentGuidL2());
            if ($this->acl->interact($parent, $owner)) {
                return $this->canInteractWithParentTree($parent);
            } else {
                return false;
            }
        }

        if ($comment->getParentGuidL1()) {
            $parent = $this->get($comment->getEntityGuid(), $comment->getParentPath(), $comment->getParentGuidL1());
            if (!$this->acl->interact($parent, $owner)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get direct comment parent. If passed a level 2 comment, will return the level 1 parent comment.
     * If passed a level 1 comment, will return the level 0 parent. If passes a level comment, will
     * return null.
     * @param Comment $comment - comment to get direct parent comment for.
     * @return Comment|null - direct parent comment or null if direct parent is not found.
     */
    public function getDirectParent(Comment $comment): ?Comment
    {
        if ($comment->getParentGuidL2()) {
            return $this->get($comment->getEntityGuid(), $comment->getParentPath(), $comment->getParentGuidL2());
        }
        if ($comment->getParentGuidL1()) {
            return $this->get($comment->getEntityGuid(), $comment->getParentPath(), $comment->getParentGuidL1());
        }
        return null;
    }

    /**
     * Injects pinned comments to start of the given response array.
     * @param Response $commentsResponse - response to inject into.
     * @param array $opts - options to pass to getList.
     * @return Response
     */
    public function injectPinnedComments(Response $commentsResponse, array $opts): Response
    {
        try {
            $pinnedComments = $this->repository->getList([
                ...$opts,
                'limit' => null,
                'exclude_pinned' => false,
                'only_pinned' => true,
            ]);

            if ($pinnedComments?->count()) {
                $commentsResponse->pushArray(
                    $this->filterResponse($pinnedComments)?->toArray() ?? []
                );
            }
        } catch (\Exception $e) {
            // Fallback to non-pinned only on error.
            $this->logger->error($e->getMessage());
        }

        return $commentsResponse;
    }
}
