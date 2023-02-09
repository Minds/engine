<?php

namespace Spec\Minds\Core\Feeds\Activity;

use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Activity\Manager;
use Minds\Core\Feeds\Activity\Controller;
use Minds\Core\Feeds\Scheduled\EntityTimeCreated;
use Minds\Core\Settings\Manager as UserSettingsManager;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
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

    private Collaborator $config;
    private Collaborator $userSettingsManager;

    public function let(
        Manager $manager,
        EntitiesBuilder $entitiesBuilder,
        ACL $acl,
        EntityTimeCreated $entityTimeCreated,
        Config $config,
        UserSettingsManager $userSettingsManager
    ) {
        $this->manager = $manager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->acl = $acl;
        $this->entityTimeCreated = $entityTimeCreated;
        $this->config = $config;
        $this->userSettingsManager = $userSettingsManager;

        $this->beConstructedWith(
            $manager,
            $entitiesBuilder,
            $acl,
            $entityTimeCreated,
            $config,
            $userSettingsManager
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
