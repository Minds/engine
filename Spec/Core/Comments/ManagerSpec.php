<?php

namespace Spec\Minds\Core\Comments;

use Minds\Common\Repository\Response;
use Minds\Core\Comments\Comment;
use Minds\Core\Comments\Delegates\CountCache;
use Minds\Core\Comments\Delegates\CreateEventDispatcher;
use Minds\Core\Comments\Delegates\Metrics;
use Minds\Core\Comments\Legacy\Repository as LegacyRepository;
use Minds\Core\Comments\RelationalRepository;
use Minds\Core\Comments\Repository;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Log\Logger;
use Minds\Core\Luid;
use Minds\Core\Security\ACL;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RbacGatekeeperService;
use Minds\Core\Security\Spam;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Entities\User;
use Minds\Exceptions\BlockedUserException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var LegacyRepository */
    protected $legacyRepository;

    /** @var ACL */
    protected $acl;

    /** @var Metrics */
    protected $metrics;

    /** @var CreateEventDispatcher */
    protected $createEventDispatcher;

    /** @var CountCache */
    protected $countCache;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Security\Spam */
    protected $spam;

    /** @var KeyValueLimiter */
    protected $kvLimiter;

    private Collaborator $rbacGatekeeperServiceMock;

    private Collaborator $loggerMock;
    
    public function let(
        Repository $repository,
        LegacyRepository $legacyRepository,
        ACL $acl,
        Metrics $metrics,
        CreateEventDispatcher $createEventDispatcher,
        CountCache $countCache,
        EntitiesBuilder $entitiesBuilder,
        Spam $spam,
        KeyValueLimiter $kvLimiter,
        EventsDispatcher $eventsDispatcher,
        RelationalRepository $relationalRepository,
        RbacGatekeeperService $rbacGatekeeperServiceMock,
        Logger $loggerMock
    ) {
        $this->beConstructedWith(
            $repository,
            $legacyRepository,
            $acl,
            $metrics,
            $createEventDispatcher,
            $countCache,
            $entitiesBuilder,
            $spam,
            $kvLimiter,
            $eventsDispatcher,
            $relationalRepository,
            $rbacGatekeeperServiceMock,
            $loggerMock
        );

        $this->repository = $repository;
        $this->legacyRepository = $legacyRepository;
        $this->acl = $acl;
        $this->metrics = $metrics;
        $this->createEventDispatcher = $createEventDispatcher;
        $this->countCache = $countCache;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->spam = $spam;
        $this->kvLimiter = $kvLimiter;
        $this->rbacGatekeeperServiceMock = $rbacGatekeeperServiceMock;
        $this->loggerMock = $loggerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Comments\Manager');
    }

    public function it_should_add(
        Comment $comment,
        Entity $entity,
        User $owner
    ) {
        $comment->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->entitiesBuilder->single(1000)->willReturn($owner);

        $comment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn(5000);

        $comment->getParentGuidL2()
            ->willReturn(null);

        $comment->getParentGuidL1()
            ->willReturn(null);

        $comment->getUrn()
            ->willReturn('urn:comment:fake');

        $comment->getGuid()
            ->willReturn('123');

        $comment->getParentPath()
            ->willReturn('0:0:0');

        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_COMMENT)->willReturn(true);

        $this->entitiesBuilder->single(5000)
            ->shouldBeCalled()
            ->willReturn($entity);
        
        $this->spam->check($comment)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->spam->check($entity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->kvLimiterMock();

        /*$entity->get('guid')
            ->shouldBeCalled()
            ->willReturn(5000);*/

        $this->acl->interact($entity, $owner, 'comment')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->legacyRepository->isFallbackEnabled()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->legacyRepository->add($comment, Repository::$allowedEntityAttributes, false)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->add($comment)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->metrics->push($comment)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->createEventDispatcher->dispatch($comment)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->countCache->destroy($comment)
            ->shouldBeCalled()
            ->willReturn(null);

        $this
            ->add($comment)
            ->shouldReturn(true);
    }

    public function it_should_throw_if_rate_limited_user_during_add(
        Comment $comment,
        Entity $entity,
        User $owner
    ) {
        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_COMMENT)->willReturn(true);
    
        $comment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn(5000);

        $this->entitiesBuilder->single(4000)->willReturn($owner);

        $comment->getOwnerGuid()
            ->willReturn(4000);

        $this->entitiesBuilder->single(5000)
            ->shouldBeCalled()
            ->willReturn($entity);

        /*$entity->get('guid')
            ->shouldBeCalled()
            ->willReturn(100);*/

        $this->kvLimiterMock();

        $this->acl->interact($entity, $owner, "comment")
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->shouldThrow(\Exception::class)
            ->duringAdd($comment);
    }

    public function it_should_throw_if_blocked_user_during_add(
        Comment $comment,
        Entity $entity,
        User $owner
    ) {
        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_COMMENT)->willReturn(true);

        $comment->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->entitiesBuilder->single(1000)->willReturn($owner);

        $comment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn(5000);

        $this->entitiesBuilder->single(5000)
            ->shouldBeCalled()
            ->willReturn($entity);

        /*$entity->get('guid')
            ->shouldBeCalled()
            ->willReturn(100);*/

        $this->kvLimiterMock();

        $this->acl->interact($entity, $owner, "comment")
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->shouldThrow(BlockedUserException::class)
            ->duringAdd($comment);
    }

    public function it_should_update(
        Comment $comment,
        Activity $entity
    ) {
        $comment->getDirtyAttributes()
            ->shouldBeCalled()
            ->willReturn(['body']);

        $comment->getUrn()
            ->willReturn('urn:comment:fake');

        $comment->getEntityGuid()
            ->willReturn('234');

        $this->entitiesBuilder->single('234')
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->spam->check($comment)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->spam->check($entity)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->legacyRepository->isFallbackEnabled()
            ->shouldBeCalled()
            ->wilLReturn(true);

        $this->legacyRepository->add($comment, ['body'], true)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->update($comment, ['body'])
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->update($comment)
            ->shouldReturn(true);
    }


    public function it_should_restore(
        Comment $comment,
        Entity $entity,
        User $owner
    ) {
        $comment->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->entitiesBuilder->single(1000)->willReturn($owner);

        $comment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn(5000);

        $this->entitiesBuilder->single(5000)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->acl->interact($entity, $owner)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->legacyRepository->isFallbackEnabled()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->legacyRepository->add($comment, Repository::$allowedEntityAttributes, false)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->add($comment)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->countCache->destroy($comment)
            ->shouldBeCalled()
            ->willReturn(null);

        $this
            ->restore($comment)
            ->shouldReturn(true);
    }

    public function it_should_throw_if_blocked_user_during_restore(
        Comment $comment,
        Entity $entity,
        User $owner
    ) {
        $comment->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->entitiesBuilder->single(1000)->willReturn($owner);

        $comment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn(5000);

        $this->entitiesBuilder->single(5000)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->acl->interact($entity, $owner)
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->shouldThrow(BlockedUserException::class)
            ->duringRestore($comment);
    }

    public function it_should_delete(
        Comment $comment
    ) {
        $comment->getUrn()
            ->willReturn('urn:comment:fake');

        $this->acl->write($comment)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->repository->delete($comment)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->countCache->destroy($comment)
            ->shouldBeCalled()
            ->willReturn(null);

        $this
            ->delete($comment)
            ->shouldReturn(true);
    }

    public function it_should_not_delete_if_acl_catches(
        Comment $comment
    ) {
        $this->acl->write($comment)
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->delete($comment)
            ->shouldReturn(false);
    }

    public function it_should_get_by_luid(
        Comment $comment
    ) {
        $this->repository->get('5000', null, '6000')
            ->shouldBeCalled()
            ->willReturn($comment);

        $this
            ->getByLuid(
                (new Luid())
                    ->setType('comment')
                    ->setEntityGuid(5000)
                    ->setParentGuid(0)
                    ->setGuid(6000)
                    ->build()
            )
            ->shouldReturn($comment);
    }

    public function it_should_fallback_to_legacy_if_throws_because_old_guid_during_get_by_luid(
        Comment $comment
    ) {
        $this->repository->get(Argument::cetera())
            ->shouldNotBeCalled();

        $this->legacyRepository->getByGuid('100000000000000000')
            ->shouldBeCalled()
            ->willReturn($comment);

        $this
            ->getByLuid('100000000000000000')
            ->shouldReturn($comment);
    }

    public function it_should_return_null_if_throws_during_get_by_luid()
    {
        $this->repository->get(Argument::cetera())
            ->shouldNotBeCalled();

        $this->legacyRepository->getByGuid(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->getByLuid('~123q11˜ñn')
            ->shouldReturn(null);
    }

    public function it_should_skip_cache(Comment $comment)
    {
        $this->repository->get('5000', '6000:0:0', '7000')
            ->shouldBeCalledTimes(2)
            ->willReturn($comment);

        $this
            ->getByUrn("urn:comment:5000:6000:0:0:7000", true) // skip_cache=true
            ->shouldReturn($comment);

        $this
            ->getByUrn("urn:comment:5000:6000:0:0:7000")
            ->shouldReturn($comment);
    }

    public function it_should_count()
    {
        $this->repository->count(5000, 0)
            ->shouldBeCalled()
            ->willReturn(3);

        $this
            ->count(5000, 0)
            ->shouldReturn(3);
    }

    public function it_should_return_zero_if_throws_during_count()
    {
        $this->repository->count(5000, 0)
            ->willThrow(new \Exception());

        $this
            ->count(5000, 0)
            ->shouldReturn(0);
    }

    public function it_should_get_direct_parent_of_tier_2_comment(
        Comment $paramComment,
        Comment $returnComment
    ) {
        $parentGuid = '123';
        $entityGuid = '234';
        $parentPath = 'path:234:123';

        $paramComment->getParentGuidL2()
            ->shouldBeCalledTimes(2)
            ->willReturn($parentGuid);

        $paramComment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $paramComment->getParentPath()
            ->shouldBeCalled()
            ->willReturn($parentPath);

        $this->repository->get($entityGuid, $parentPath, $parentGuid)
            ->shouldBeCalled()
            ->willReturn($returnComment);

        $this->getDirectParent($paramComment)
            ->shouldBe($returnComment);
    }

    public function it_should_get_direct_parent_of_tier_1_comment(
        Comment $paramComment,
        Comment $returnComment
    ) {
        $parentGuid = '123';
        $entityGuid = '234';
        $parentPath = 'path:234:123';

        $paramComment->getParentGuidL2()
            ->shouldBeCalledTimes(1)
            ->willReturn(null);
        
        $paramComment->getParentGuidL1()
            ->shouldBeCalledTimes(2)
            ->willReturn($parentGuid);

        $paramComment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $paramComment->getParentPath()
            ->shouldBeCalled()
            ->willReturn($parentPath);

        $this->repository->get($entityGuid, $parentPath, $parentGuid)
            ->shouldBeCalled()
            ->willReturn($returnComment);

        $this->getDirectParent($paramComment)
            ->shouldBe($returnComment);
    }

    public function it_should_get_no_direct_parent_for_a_tier_0_comment(
        Comment $paramComment,
    ) {
        $paramComment->getParentGuidL2()
            ->shouldBeCalledTimes(1)
            ->willReturn(null);
        
        $paramComment->getParentGuidL1()
            ->shouldBeCalledTimes(1)
            ->willReturn(null);

        $paramComment->getEntityGuid()
            ->shouldNotBeCalled();

        $paramComment->getParentPath()
            ->shouldNotBeCalled();

        $this->repository->get(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->getDirectParent($paramComment)
            ->shouldBe(null);
    }

    public function it_should_inject_pinned_comments(
        Response $commentsResponse,
        Response $pinnedCommentsResponse,
        Response $resultResponse,
        Comment $pinnedComment,
        User $pinnedCommentOwner,
        Entity $pinnedCommentEntity
    ) {
        $pinnedCommentEntityGuid = '123';
        $pinnedCommentOwnerGuid = '456';

        $opts = [
            'limit' => 12,
            'offset' => 0
        ];

        $pinnedComment->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($pinnedCommentEntityGuid);

        $pinnedComment->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($pinnedCommentOwnerGuid);

        $pinnedCommentsResponse->count()
            ->shouldBeCalled()
            ->willReturn(1);

        $pinnedCommentsResponse->isLastPage()
            ->shouldBeCalled()
            ->willReturn(true);

        $pinnedCommentsResponse->getPagingToken()
            ->shouldBeCalled()
            ->willReturn(null);

        $pinnedCommentsResponse->rewind()
            ->shouldBeCalled();

        $pinnedCommentsResponse->next()
            ->shouldBeCalled();

        $pinnedCommentsResponseValidCallCount = 0;
        $pinnedCommentsResponse->valid()
            ->shouldBeCalled()
            ->will(function () use (&$pinnedCommentsResponseValidCallCount) {
                $pinnedCommentsResponseValidCallCount++;
                return $pinnedCommentsResponseValidCallCount === 1;
            });

        $pinnedCommentsResponseCurrentCallCount = 0;
        $pinnedCommentsResponse->current()
            ->shouldBeCalled()
            ->will(function () use (&$pinnedCommentsResponseCurrentCallCount, $pinnedComment) {
                $pinnedCommentsResponseCurrentCallCount++;
                return $pinnedCommentsResponseCurrentCallCount === 1 ?
                        $pinnedComment :
                        null;
            });

            
        $this->repository->getList([
            'limit' => null,
            'exclude_pinned' => false,
            'only_pinned' => true,
            'offset' => 0
        ])
            ->shouldBeCalled()
            ->willReturn($pinnedCommentsResponse);

        $this->entitiesBuilder->single($pinnedCommentEntityGuid)
            ->shouldBeCalled()
            ->willReturn($pinnedCommentEntity);

        
        $this->entitiesBuilder->single($pinnedCommentOwnerGuid)
            ->shouldBeCalled()
            ->willReturn($pinnedCommentOwner);

        $this->acl->read($pinnedComment)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->acl->interact($pinnedCommentEntity, $pinnedCommentOwner)
            ->shouldBeCalled()
            ->willReturn(true);

        $commentsResponse->pushArray([$pinnedComment])
            ->shouldBeCalled()
            ->willReturn($resultResponse);

        $this->injectPinnedComments($commentsResponse, $opts)
            ->shouldReturn($commentsResponse);
    }

    private function kvLimiterMock()
    {
        $this->kvLimiter->setKey(Argument::any())->willReturn($this->kvLimiter);
        $this->kvLimiter->setValue(Argument::any())->willReturn($this->kvLimiter);
        $this->kvLimiter->setSeconds(Argument::any())->willReturn($this->kvLimiter);
        $this->kvLimiter->setMax(Argument::any())->willReturn($this->kvLimiter);
        $this->kvLimiter->checkAndIncrement()->willReturn(true);
    }
}
