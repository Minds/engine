<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Monetization\Demonetization\Strategies;

use Minds\Core\Blogs\Blog;
use Minds\Core\Monetization\Demonetization\Strategies\DemonetizePostStrategy;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Entities\GuidLinkResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class DemonetizePostStrategySpec extends ObjectBehavior
{
    protected Collaborator $saveAction;
    protected Collaborator $entitiesBuilder;
    protected Collaborator $guidLinkResolver;
    protected Collaborator $logger;

    public function let(
        SaveAction $saveAction,
        EntitiesBuilder $entitiesBuilder,
        GuidLinkResolver $guidLinkResolver,
        Logger $logger
    ) {
        $this->beConstructedWith(
            $saveAction,
            $entitiesBuilder,
            $guidLinkResolver,
            $logger
        );
        $this->saveAction = $saveAction;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->guidLinkResolver = $guidLinkResolver;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(DemonetizePostStrategy::class);
    }

    public function it_should_not_execute_if_entity_is_not_paywall_entity_interface_instance(
        User $entity
    ) {
        $this->shouldThrow(ServerErrorException::class)->during('execute', [$entity]);
    }

    public function it_should_execute_for_activity(
        Activity $entity
    ) {
        $entity->setWireThreshold([])
            ->shouldBeCalled();
        
        $entity->setPaywall(false)
            ->shouldBeCalled();

        $this->saveAction->setEntity($entity)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->save(true)
            ->shouldBeCalled();

        $this->execute($entity);
    }

    public function it_should_execute_for_image(
        Image $entity,
        Activity $activity
    ) {
        $entityGuid = '123';
        $activityGuid = 123;

        $entity->setWireThreshold([])
            ->shouldBeCalled();
        
        $entity->setPaywall(false)
            ->shouldBeCalled();

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $this->guidLinkResolver->resolve($entityGuid)
            ->shouldBeCalled()
            ->willReturn($activityGuid);

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->saveAction->setEntity($activity)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->setEntity($entity)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);
        
        $activity->setWireThreshold([])
            ->shouldBeCalled();
        
        $activity->setPaywall(false)
            ->shouldBeCalled();

        $this->saveAction->save(true)
            ->shouldBeCalledTimes(2);

        $this->execute($entity);
    }

    public function it_should_execute_for_video(
        Video $entity,
        Activity $activity
    ) {
        $entityGuid = '123';
        $activityGuid = 123;

        $entity->setWireThreshold([])
            ->shouldBeCalled();
        
        $entity->setPaywall(false)
            ->shouldBeCalled();

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $this->guidLinkResolver->resolve($entityGuid)
            ->shouldBeCalled()
            ->willReturn($activityGuid);

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->saveAction->setEntity($activity)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->setEntity($entity)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);
        
        $activity->setWireThreshold([])
            ->shouldBeCalled();
        
        $activity->setPaywall(false)
            ->shouldBeCalled();

        $this->saveAction->save(true)
            ->shouldBeCalledTimes(2);

        $this->execute($entity);
    }

    public function it_should_execute_for_blog(
        Blog $entity,
        Activity $activity
    ) {
        $entityGuid = '123';
        $activityGuid = 123;

        $entity->setWireThreshold([])
            ->shouldBeCalled();
        
        $entity->setPaywall(false)
            ->shouldBeCalled();

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $this->guidLinkResolver->resolve($entityGuid)
            ->shouldBeCalled()
            ->willReturn($activityGuid);

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->saveAction->setEntity($activity)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->setEntity($entity)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);
        
        $activity->setWireThreshold([])
            ->shouldBeCalled();
        
        $activity->setPaywall(false)
            ->shouldBeCalled();

        $this->saveAction->save(true)
            ->shouldBeCalledTimes(2);

        $this->execute($entity);
    }
}
