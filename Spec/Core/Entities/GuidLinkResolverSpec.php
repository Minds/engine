<?php

namespace Spec\Minds\Core\Entities;

use Minds\Core\Data\Call;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;

class GuidLinkResolverSpec extends ObjectBehavior
{
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Call */
    protected $db;

    public function let(
        EntitiesBuilder $entitiesBuilder,
        Call $db
    ) {
        $this->entitiesBuilder = $entitiesBuilder;
        $this->db = $db;
        $this->beConstructedWith($entitiesBuilder, $db);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Entities\GuidLinkResolver');
    }

    public function it_should_resolve_linked_activity_from_entity_guid()
    {
        $entityGuid = "123";
        $activityGuid = "321";

        $this->db->getRow('activity:entitylink:123')
            ->shouldBeCalled()
            ->willReturn([
                $activityGuid => $activityGuid
            ]);

        $this->resolve($entityGuid)->shouldBe($activityGuid);
    }


    public function it_should_resolve_linked_entity_from_activity_guid(
        Activity $activity
    ) {
        $activityGuid = "321";
        $entityGuid = "123";

        // mocking no linked activity found because this IS an activity guid
        $this->db->getRow('activity:entitylink:321')
            ->shouldBeCalled()
            ->willReturn([]);

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $this->resolve($activityGuid)->shouldBe($entityGuid);
    }

    public function it_should_return_null_if_no_match_is_found(
        Activity $activity
    ) {
        $activityGuid = "321";

        // mocking no linked activity found because this IS an activity guid
        $this->db->getRow('activity:entitylink:321')
            ->shouldBeCalled()
            ->willReturn([]);

        $this->entitiesBuilder->single($activityGuid)
            ->shouldBeCalled()
            ->willReturn($activity);

        $activity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->resolve($activityGuid)->shouldBe(null);
    }
}
