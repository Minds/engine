<?php

namespace Spec\Minds\Core\Feeds\Activity;

use Http\Factory\Guzzle\ServerRequestFactory;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Activity\Manager;
use Minds\Core\Feeds\Activity\Controller;
use Minds\Core\Feeds\Scheduled\EntityTimeCreated;
use Minds\Core\Monetization\Demonetization\Validators\DemonetizedPlusValidator;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\CreatePaywalledEntityService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RbacGatekeeperService;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\AlreadyPublishedException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use PDO;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    private $manager;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var ACL */
    private $acl;

    /** @var EntityTimeCreated */
    private $entityTimeCreated;

    private Collaborator $demonetizedPlusValidator;
    private Collaborator $rbacGatekeeperServiceMock;
    private Collaborator $createPaywalledEntityServiceMock;

    public function let(
        Manager $manager,
        EntitiesBuilder $entitiesBuilder,
        ACL $acl,
        EntityTimeCreated $entityTimeCreated,
        DemonetizedPlusValidator $demonetizedPlusValidator,
        RbacGatekeeperService $rbacGatekeeperServiceMock,
        CreatePaywalledEntityService $createPaywalledEntityServiceMock,
    ) {
        $this->manager = $manager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->acl = $acl;
        $this->entityTimeCreated = $entityTimeCreated;
        $this->demonetizedPlusValidator = $demonetizedPlusValidator;
        $this->rbacGatekeeperServiceMock = $rbacGatekeeperServiceMock;
        $this->createPaywalledEntityServiceMock = $createPaywalledEntityServiceMock;

        $this->beConstructedWith(
            $manager,
            $entitiesBuilder,
            $acl,
            $entityTimeCreated,
            $demonetizedPlusValidator,
            $rbacGatekeeperServiceMock,
            $createPaywalledEntityServiceMock,
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_allow_the_update_of_scheduled_time(
        ServerRequest $serverRequest,
        Activity $mutatedActivity,
    ) {
        $activityGuid = '123';
        $updatedCreationTimestamp = strtotime('midnight tomorrow');
        
        $serverRequest->getAttribute('_user')->willReturn(new User());
        $serverRequest->getAttribute('parameters')->willReturn([ 'guid' => $activityGuid ]);
        
        $serverRequest->getParsedBody()
            ->shouldBeCalled()
            ->willReturn([
                'time_created' => $updatedCreationTimestamp,
            ]);

        $mutatedActivity->canEdit()
            ->shouldBeCalled()
            ->willReturn(true);

        $mutatedActivity->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn($updatedCreationTimestamp);

        $mutatedActivity->setMature(false)
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $mutatedActivity->setNsfw([])
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $mutatedActivity->setLicense('')
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $this->entityTimeCreated->validate(
            $mutatedActivity,
            $updatedCreationTimestamp,
            Argument::type('int'),
            2
        )->shouldBeCalled();

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $this->manager->update(Argument::that(function ($arg) use ($updatedCreationTimestamp) {
            return $arg->getMutatedEntity()->getTimeCreated() == $updatedCreationTimestamp;
        }))
            ->shouldBeCalled()
            ->willReturn(false);

        /**
         * the design of this function means we cannot check the exported entity or get the entity mutation
         * because we construct a new EntityMutation within the function - as a result here we are asserting that
         * update was called with the correct parameter above to verify the specified behavior, and are not able
         * to verifying the functionality on save success.
         */
        $this->shouldThrow(new ServerErrorException("The post could not be saved."))->duringUpdateExistingActivity($serverRequest);
    }

    public function it_should_NOT_allow_the_update_of_scheduled_time_for_a_published_post_but_should_silently_fail(
        ServerRequest $serverRequest,
        Activity $mutatedActivity,
    ) {
        $activityGuid = '123';
        $updatedCreationTimestamp = strtotime('midnight tomorrow');
        
        $serverRequest->getAttribute('_user')->willReturn(new User());
        $serverRequest->getAttribute('parameters')->willReturn([ 'guid' => $activityGuid ]);
        
        $serverRequest->getParsedBody()
            ->shouldBeCalled()
            ->willReturn([
                'time_created' => $updatedCreationTimestamp,
            ]);

        $mutatedActivity->canEdit()
            ->shouldBeCalled()
            ->willReturn(true);

        $mutatedActivity->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn($updatedCreationTimestamp);

        // Not to be called for this test case.
        $mutatedActivity->setTimeCreated($updatedCreationTimestamp)
            ->shouldNotBeCalled()
            ->willReturn();

        $mutatedActivity->setMature(false)
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $mutatedActivity->setNsfw([])
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $mutatedActivity->setLicense('')
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $this->entityTimeCreated->validate(
            $mutatedActivity,
            $updatedCreationTimestamp,
            Argument::type('int'),
            2
        )
            ->shouldBeCalled()
            ->willThrow(new AlreadyPublishedException());

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $this->manager->update(Argument::that(function ($arg) use ($updatedCreationTimestamp) {
            return $arg->getMutatedEntity()->getTimeCreated() == $updatedCreationTimestamp;
        }))
            ->shouldBeCalled()
            ->willReturn(false);

        /**
         * the design of this function means we cannot check the exported entity or get the entity mutation
         * because we construct a new EntityMutation within the function - as a result here we are asserting that
         * update was called with the correct parameter above to verify the specified behavior, and are not able
         * to verifying the functionality on save success.
         */
        $this->shouldThrow(new ServerErrorException("The post could not be saved."))->duringUpdateExistingActivity($serverRequest);
    }

    public function it_should_NOT_allow_the_update_of_a_post_when_scheduled_time_validation_fails_with_unhandled_exception(
        ServerRequest $serverRequest,
        Activity $mutatedActivity,
    ) {
        $activityGuid = '123';
        $updatedCreationTimestamp = strtotime('midnight tomorrow');
        
        $serverRequest->getAttribute('_user')->willReturn(new User());
        $serverRequest->getAttribute('parameters')->willReturn([ 'guid' => $activityGuid ]);
        
        $serverRequest->getParsedBody()
            ->shouldBeCalled()
            ->willReturn([
                'time_created' => $updatedCreationTimestamp,
            ]);

        $mutatedActivity->canEdit()
            ->shouldBeCalled()
            ->willReturn(true);

        $mutatedActivity->getTimeCreated()
            ->shouldNotBeCalled()
            ->willReturn($updatedCreationTimestamp);

        // Not to be called for this test case.
        $mutatedActivity->setTimeCreated($updatedCreationTimestamp)
            ->shouldNotBeCalled()
            ->willReturn();

        $mutatedActivity->setMature(false)
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $mutatedActivity->setNsfw([])
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $mutatedActivity->setLicense('')
            ->shouldNotBeCalled()
            ->willReturn($mutatedActivity);

        $this->entityTimeCreated->validate(
            $mutatedActivity,
            $updatedCreationTimestamp,
            Argument::type('int'),
            2
        )
            ->shouldBeCalled()
            ->willThrow(new UserErrorException());

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($mutatedActivity);

        $this->manager->update(Argument::that(function ($arg) use ($updatedCreationTimestamp) {
            return $arg->getMutatedEntity()->getTimeCreated() == $updatedCreationTimestamp;
        }))
            ->shouldNotBeCalled()
            ->willReturn(false);

        /**
         * the design of this function means we cannot check the exported entity or get the entity mutation
         * because we construct a new EntityMutation within the function - as a result here we are asserting that
         * update was called with the correct parameter above to verify the specified behavior, and are not able
         * to verifying the functionality on save success.
         */
        $this->shouldThrow(new UserErrorException())->duringUpdateExistingActivity($serverRequest);
    }

    // public function it_should_set_scheduled_post(ServerRequest $serverRequest)
    // {
    //     $serverRequest->getAttribute('_user')->willReturn(new User());
    //     $serverRequest->getParsedBody()
    //         ->willReturn([
    //             'time_created' => strtotime('midnight tomorrow'),
    //         ]);

    //     $this->managerMock->add(Argument::that(function ($activity) {
    //         $activity->guid = '123';

    //         return $activity->getTimeCreated() === strtotime('midnight tomorrow');
    //     }))
    //         ->shouldBeCalled()
    //         ->willReturn(true);

    //     $this->createNewActivity($serverRequest);
    // }

    public function it_should_disallow_site_membership_if_no_permission(ServerRequestInterface $requestMock)
    {
        $requestMock->getAttribute('_user')
            ->willReturn(new User);

        $requestMock->getParsedBody()
            ->willReturn([
                'site_membership_guids' => [ -1 ]
            ]);

        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_CREATE_PAYWALL, Argument::type(User::class))
            ->willThrow(new ForbiddenException);
        
        $this->shouldThrow(ForbiddenException::class)->duringCreateNewActivity($requestMock);
    }

    public function it_should_set_site_memberships(ServerRequestInterface $requestMock, Activity $activityMock)
    {
        $requestMock->getAttribute('_user')
            ->willReturn(new User);

        $requestMock->getParsedBody()
            ->willReturn([
                'site_membership_guids' => [ -1 ]
            ]);

        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_CREATE_PAYWALL, Argument::type(User::class))
            ->willReturn(true);
        
        $this->createPaywalledEntityServiceMock->setupMemberships(Argument::type(Activity::class), [ -1 ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->manager->add(Argument::type(Activity::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $activityMock->setLicense(Argument::type('string'))
            ->willReturn($activityMock);
        $activityMock->setClientMeta(Argument::type('array'))
            ->willReturn($activityMock);
        $activityMock->setMature(false)
            ->willReturn($activityMock);
        $activityMock->setNsfw([])
            ->willReturn($activityMock);

        $activityMock->export()
            ->willReturn([]);

        $this->createNewActivity($requestMock, $activityMock);
    }

    public function it_should_set_site_membership_poster(ServerRequestInterface $requestMock, Activity $activityMock)
    {
        $requestMock->getAttribute('_user')
            ->willReturn(new User);

        $requestMock->getParsedBody()
            ->willReturn([
                'site_membership_guids' => [ -1 ],
                'paywall_thumbnail' => 'blob'
            ]);

        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_CREATE_PAYWALL, Argument::type(User::class))
            ->willReturn(true);
        
        $this->createPaywalledEntityServiceMock->setupMemberships(Argument::type(Activity::class), [ -1 ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->manager->add(Argument::type(Activity::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->createPaywalledEntityServiceMock->processPaywallThumbnail(Argument::type(Activity::class), 'blob')
            ->shouldBeCalled();

        $activityMock->setLicense(Argument::type('string'))
            ->willReturn($activityMock);
        $activityMock->setClientMeta(Argument::type('array'))
            ->willReturn($activityMock);
        $activityMock->setMature(false)
            ->willReturn($activityMock);
        $activityMock->setNsfw([])
            ->willReturn($activityMock);

        $activityMock->export()
            ->willReturn([]);

        $this->createNewActivity($requestMock, $activityMock);
    }
}
