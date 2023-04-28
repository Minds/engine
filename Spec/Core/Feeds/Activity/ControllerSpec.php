<?php

namespace Spec\Minds\Core\Feeds\Activity;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Activity\Manager;
use Minds\Core\Feeds\Activity\Controller;
use Minds\Core\Feeds\Scheduled\EntityTimeCreated;
use Minds\Core\Monetization\Demonetization\Validators\DemonetizedPlusValidator;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\AlreadyPublishedException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
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

    public function let(
        Manager $manager,
        EntitiesBuilder $entitiesBuilder,
        ACL $acl,
        EntityTimeCreated $entityTimeCreated,
        DemonetizedPlusValidator $demonetizedPlusValidator,
    ) {
        $this->manager = $manager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->acl = $acl;
        $this->entityTimeCreated = $entityTimeCreated;
        $this->demonetizedPlusValidator = $demonetizedPlusValidator;

        $this->beConstructedWith(
            $manager,
            $entitiesBuilder,
            $acl,
            $entityTimeCreated,
            $demonetizedPlusValidator,
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

        $mutatedActivity->setTimeCreated($updatedCreationTimestamp)
            ->shouldBeCalled()
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
}
