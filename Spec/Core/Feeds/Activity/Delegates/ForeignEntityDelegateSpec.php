<?php

namespace Spec\Minds\Core\Feeds\Activity\Delegates;

use Minds\Core\Feeds\Activity\Delegates\ForeignEntityDelegate;
use Minds\Common\EntityMutation;
use Minds\Entities\Activity;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\PropagateProperties;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ForeignEntityDelegateSpec extends ObjectBehavior
{
    /** @var Save */
    protected $save;

    /** @var PropagateProperties */
    private $propagateProperties;

    public function let(Save $save, PropagateProperties $propagateProperties)
    {
        $this->beConstructedWith($save, $propagateProperties);

        $this->save = $save;
        $this->propagateProperties = $propagateProperties;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ForeignEntityDelegate::class);
    }

    public function it_should_update_a_video_entity()
    {
        $activity = new Activity();
        $activity->owner_guid = 123;
        $activity->type = 'object';
        $activity->subtype = 'video';
        $activity->title = 'this is my title';

        $activityMutation = new EntityMutation($activity);
        $activityMutation->setNsfw([1]);

        $this->save->setEntity(Argument::that(function ($video) {
            return $video->title === 'this is my title'
                && $video->getNsfw() === [ 1 ];
        }))
            ->willReturn($this->save);
        $this->save
            ->save()
            ->shouldBeCalled();

        $this->onUpdate($activity, $activityMutation);
    }

    public function it_should_update_an_image_entity()
    {
        $activity = new Activity();
        $activity->owner_guid = 123;
        $activity->type = 'object';
        $activity->subtype = 'image';
        $activity->title = 'this is my title';

        $activityMutation = new EntityMutation($activity);
        $activityMutation->setNsfw([1]);

        $this->save->setEntity(Argument::that(function ($image) {
            return $image->title === 'this is my title'
                && $image->getNsfw() === [ 1 ];
        }))
            ->willReturn($this->save);
        $this->save
            ->save()
            ->shouldBeCalled();

        $this->onUpdate($activity, $activityMutation);
    }
}
