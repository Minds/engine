<?php

namespace Spec\Minds\Core\Analytics\Clicks;

use Minds\Core\Analytics\Clicks\Delegates\ActionEventsDelegate as ClickActionEventsDelegate;
use Minds\Core\Analytics\Clicks\Delegates\PostHogDelegate;
use Minds\Core\Analytics\Clicks\Manager;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var ClickActionEventsDelegate */
    private Collaborator $actionEventsDelegate;

    /** @var PostHogDelegate */
    private Collaborator $postHogDelegate;

    /** @var EntitiesBuilder */
    private Collaborator $entitiesBuilder;

    /** @var EntitiesResolver */
    private Collaborator $entitiesResolver;

    public function let(
        ClickActionEventsDelegate $actionEventsDelegate,
        PostHogDelegate $postHogDelegate,
        EntitiesBuilder $entitiesBuilder,
        EntitiesResolver $entitiesResolver
    ) {
        $this->actionEventsDelegate = $actionEventsDelegate;
        $this->postHogDelegate = $postHogDelegate;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->entitiesResolver = $entitiesResolver;

        $this->beConstructedWith(
            $actionEventsDelegate,
            $postHogDelegate,
            $entitiesBuilder,
            $entitiesResolver
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_track_boost_click(
        User $user,
        EntityInterface $boost
    ): void {
        $entityGuid = '123';
        $campaignUrn = 'urn:boost:234';
        $clientMeta = [
            'platform' => 'cli',
            'campaign' => $campaignUrn
        ];

        $this->entitiesResolver->single($campaignUrn)
            ->shouldBeCalled()
            ->willReturn($boost);

        $this->actionEventsDelegate->onClick($boost, $user)
            ->shouldBeCalled();

        $this->postHogDelegate->onClick($boost, $clientMeta, $user)
            ->shouldBeCalled();

        $this->trackClick($entityGuid, $clientMeta, $user);
    }

    public function it_should_throw_not_found_exception_when_boost_not_found(
        User $user
    ): void {
        $entityGuid = '123';
        $campaignUrn = 'urn:boost:234';
        $clientMeta = [
            'platform' => 'cli',
            'campaign' => $campaignUrn
        ];

        $this->entitiesResolver->single($campaignUrn)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->actionEventsDelegate->onClick(Argument::any(), $user)
            ->shouldNotBeCalled();

        $this->postHogDelegate->onClick(Argument::any(), $clientMeta, $user)
            ->shouldNotBeCalled();

        $this->shouldThrow(NotFoundException::class)->during('trackClick', [$entityGuid, $clientMeta, $user]);
    }

    public function it_should_track_non_boost_click(
        User $user,
        EntityInterface $entity
    ): void {
        $entityGuid = '123';
        $clientMeta = [ 'platform' => 'cli' ];

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalled()
            ->willReturn($entity);

        $this->actionEventsDelegate->onClick($entity, $user)
            ->shouldBeCalled();

        $this->postHogDelegate->onClick($entity, $clientMeta, $user)
            ->shouldBeCalled();

        $this->trackClick($entityGuid, $clientMeta, $user);
    }

    public function it_should_throw_not_found_exception_when_entity_not_found(User $user): void
    {
        $entityGuid = '123';
        $clientMeta = [ 'platform' => 'cli' ];

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->actionEventsDelegate->onClick(Argument::any(), $user)
            ->shouldNotBeCalled();

        $this->postHogDelegate->onClick(Argument::any(), $clientMeta, $user)
            ->shouldNotBeCalled();

        $this->shouldThrow(NotFoundException::class)->during('trackClick', [$entityGuid, $clientMeta, $user]);
    }
}
