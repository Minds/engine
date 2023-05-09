<?php

namespace Spec\Minds\Core\Boost\V3\Models;

use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Models\BoostEntityWrapper;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;

class BoostEntityWrapperSpec extends ObjectBehavior
{
    public function it_is_initializable(Boost $boost)
    {
        $this->beConstructedWith($boost);
        $this->shouldHaveType(BoostEntityWrapper::class);
    }

    public function it_should_export(Boost $boost, Activity $entity)
    {
        $entityGuid = '123';
        $exportedEntity = [ 'guid' => $entityGuid ];
        $boostGuid = '234';
        $boostUrn = 'boost:urn:345';
        $goalButtonText = null;
        $goalButtonUrl = null;

        $entity->export()
            ->shouldBeCalled()
            ->willReturn($exportedEntity);

        $boost->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn($boostGuid);

        $boost->getUrn()
            ->shouldBeCalled()
            ->willReturn($boostUrn);

        $boost->getGoalButtonText()
        ->shouldBeCalled()
        ->willReturn($goalButtonText);

        $boost->getGoalButtonUrl()
        ->shouldBeCalled()
        ->willReturn($goalButtonUrl);

        $this->beConstructedWith($boost);
        $this->export()->shouldBe([
            'guid' => $entityGuid,
            'boosted' => true,
            'boosted_guid' => $boostGuid,
            'urn' => $boostUrn,
            'goal_button_text' => null,
            'goal_button_url' => null
        ]);
    }
}
