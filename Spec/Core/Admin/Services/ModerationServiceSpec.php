<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Admin\Services;

use Minds\Core\Admin\Services\ModerationService;
use Minds\Core\Comments\Manager as CommentManager;
use Minds\Core\Channels\Ban as ChannelsBanManager;
use Minds\Core\Comments\Comment;
use Minds\Core\Entities\Actions\Delete as DeleteAction;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Security\ACL;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ModerationServiceSpec extends ObjectBehavior
{
    private Collaborator $rolesService;
    private Collaborator $channelsBanManager;
    private Collaborator $deleteAction;
    private Collaborator $commentManager;
    private Collaborator $entitiesBuilder;
    private Collaborator $entitiesResolver;
    private Collaborator $acl;

    public function let(
        RolesService $rolesService,
        ChannelsBanManager $channelsBanManager,
        DeleteAction $deleteAction,
        CommentManager $commentManager,
        EntitiesBuilder $entitiesBuilder,
        EntitiesResolver $entitiesResolver,
        ACL $acl
    ) {
        $this->rolesService = $rolesService;
        $this->channelsBanManager = $channelsBanManager;
        $this->deleteAction = $deleteAction;
        $this->commentManager = $commentManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->entitiesResolver = $entitiesResolver;
        $this->acl = $acl;

        $this->beConstructedWith(
            $this->rolesService,
            $this->channelsBanManager,
            $this->deleteAction,
            $this->commentManager,
            $this->entitiesBuilder,
            $this->entitiesResolver,
            $this->acl
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(ModerationService::class);
    }

    // Ban user

    public function it_should_ban_a_user(User $subject): void
    {
        $userGuid = Guid::build();
        $isAdmin = false;
        $hasPermission = false;
        $banState = true;

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn($subject);

        $subject->isAdmin()
            ->shouldBeCalled()
            ->willReturn($isAdmin);

        $this->rolesService->hasPermission($subject, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willReturn($hasPermission);
       
        $this->channelsBanManager->setUser($subject)->shouldBeCalled()->willReturn($this->channelsBanManager);
        $this->channelsBanManager->ban('11')->shouldBeCalled();
        $this->setUserBanState($userGuid, $banState)->shouldReturn(true);
    }

    public function it_should_unban_a_user(User $subject): void
    {
        $userGuid = Guid::build();
        $isAdmin = false;
        $hasPermission = false;
        $banState = false;

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn($subject);

        $subject->isAdmin()
            ->shouldBeCalled()
            ->willReturn($isAdmin);

        $this->rolesService->hasPermission($subject, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willReturn($hasPermission);

        $this->channelsBanManager->setUser($subject)->shouldBeCalled()->willReturn($this->channelsBanManager);
        $this->channelsBanManager->unban()->shouldBeCalled();
        $this->setUserBanState($userGuid, $banState)->shouldReturn(true);
    }

    public function it_should_NOT_ban_a_user_because_the_user_is_an_admin(User $subject): void
    {
        $userGuid = Guid::build();
        $isAdmin = true;

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn($subject);

        $subject->isAdmin()
            ->shouldBeCalled()
            ->willReturn($isAdmin);

        $this->rolesService->hasPermission($subject, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldNotBeCalled();
        
        $this->channelsBanManager->setUser($subject)->shouldNotBeCalled();

        $this->shouldThrow(UserErrorException::class)->duringSetUserBanState($userGuid, true);
    }

    public function it_should_ban_a_user_because_the_user_has_the_can_moderate_permission(User $subject): void
    {
        $userGuid = Guid::build();
        $isAdmin = false;
        $hasPermission = true;

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn($subject);

        $subject->isAdmin()
            ->shouldBeCalled()
            ->willReturn($isAdmin);

        $this->rolesService->hasPermission($subject, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willReturn($hasPermission);
        
        $this->channelsBanManager->setUser($subject)->shouldNotBeCalled();
        $this->shouldThrow(UserErrorException::class)->duringSetUserBanState($userGuid, true);
    }

    public function it_should_handle_user_not_found_scenarios_when_banning_a_user(): void
    {
        $userGuid = Guid::build();

        $this->entitiesBuilder->single($userGuid)
            ->shouldBeCalled()
            ->willReturn(null);
        
        $this->channelsBanManager->setUser(Argument::any())->shouldNotBeCalled();
        $this->shouldThrow(UserErrorException::class)->duringSetUserBanState($userGuid, true);
    }

    // Delete entity

    public function it_should_delete_an_activity(Activity $activity, User $entityOwner): void
    {
        $entityUrn = "urn:activity:".Guid::build();
        $ownerGuid = Guid::build();
        $isAdmin = false;
        $hasPermission = false;

        $this->entitiesResolver->single($entityUrn)
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($entityOwner);

        $entityOwner->isAdmin()
            ->shouldBeCalled()
            ->willReturn($isAdmin);

        $this->rolesService->hasPermission($entityOwner, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willReturn($hasPermission);
        
        $this->deleteAction->setEntity($activity)->shouldBeCalled()->willReturn($this->deleteAction);
        $this->deleteAction->delete()->shouldBeCalled();

        $this->deleteEntity($entityUrn)->shouldReturn(true);
    }

    public function it_should_delete_a_comment(Comment $comment, User $entityOwner): void
    {
        $entityUrn = "urn:activity:".Guid::build();
        $ownerGuid = Guid::build();
        $isAdmin = false;
        $hasPermission = false;

        $this->entitiesResolver->single($entityUrn)
            ->shouldBeCalled()
            ->willReturn($comment);

        $comment->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($entityOwner);

        $entityOwner->isAdmin()
            ->shouldBeCalled()
            ->willReturn($isAdmin);

        $this->rolesService->hasPermission($entityOwner, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willReturn($hasPermission);
        
        $this->commentManager->delete($comment)->shouldBeCalled();
        $this->deleteAction->setEntity($comment)->shouldNotBeCalled();

        $this->deleteEntity($entityUrn)->shouldReturn(true);
    }

    public function it_should_not_delete_an_entity_because_it_is_not_found(Activity $activity, User $entityOwner): void
    {
        $entityUrn = "urn:activity:".Guid::build();

        $this->entitiesResolver->single($entityUrn)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->commentManager->delete(Argument::any())->shouldNotBeCalled();
        $this->deleteAction->setEntity(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(UserErrorException::class)->duringDeleteEntity($entityUrn);
    }

    public function it_should_not_delete_an_activity_because_it_is_a_user(User $user, User $entityOwner): void
    {
        $entityUrn = "urn:activity:".Guid::build();

        $this->entitiesResolver->single($entityUrn)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->commentManager->delete(Argument::any())->shouldNotBeCalled();
        $this->deleteAction->setEntity(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(UserErrorException::class)->duringDeleteEntity($entityUrn);
    }

    public function it_should_not_delete_an_activity_because_the_owner_is_not_found(Activity $activity, User $entityOwner): void
    {
        $entityUrn = "urn:activity:".Guid::build();
        $ownerGuid = Guid::build();

        $this->entitiesResolver->single($entityUrn)
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->commentManager->delete(Argument::any())->shouldNotBeCalled();
        $this->deleteAction->setEntity(Argument::any())->shouldNotBeCalled();
    
        $this->shouldThrow(UserErrorException::class)->duringDeleteEntity($entityUrn);
    }

    public function it_should_not_delete_an_activity_because_the_owner_is_an_admin(Activity $activity, User $entityOwner): void
    {
        $entityUrn = "urn:activity:".Guid::build();
        $ownerGuid = Guid::build();
        $isAdmin = true;

        $this->entitiesResolver->single($entityUrn)
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($entityOwner);

        $entityOwner->isAdmin()
            ->shouldBeCalled()
            ->willReturn($isAdmin);

        $this->commentManager->delete(Argument::any())->shouldNotBeCalled();
        $this->deleteAction->setEntity(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(UserErrorException::class)->duringDeleteEntity($entityUrn);
    }

    public function it_should_not_delete_an_activity_because_the_owner_has_moderation_permissions(Activity $activity, User $entityOwner): void
    {
        $entityUrn = "urn:activity:".Guid::build();
        $ownerGuid = Guid::build();
        $isAdmin = false;
        $hasPermission = true;

        $this->entitiesResolver->single($entityUrn)
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($ownerGuid);

        $this->entitiesBuilder->single($ownerGuid)
            ->shouldBeCalled()
            ->willReturn($entityOwner);

        $entityOwner->isAdmin()
            ->shouldBeCalled()
            ->willReturn($isAdmin);

        $this->rolesService->hasPermission($entityOwner, PermissionsEnum::CAN_MODERATE_CONTENT)
            ->shouldBeCalled()
            ->willReturn($hasPermission);

        $this->commentManager->delete(Argument::any())->shouldNotBeCalled();
        $this->deleteAction->setEntity(Argument::any())->shouldNotBeCalled();
    
        $this->shouldThrow(UserErrorException::class)->duringDeleteEntity($entityUrn);
    }
}
