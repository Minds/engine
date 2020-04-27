<?php

namespace Spec\Minds\Core\Feeds\Activity;

use Minds\Core\Feeds\Activity\Manager;
use Minds\Common\EntityMutation;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\PropagateProperties;
use Minds\Core\Session;
use Minds\Core\Feeds\Activity\Delegates;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Delegates\ForeignEntityDelegate */
    protected $foreignEntityDelegate;

    /** @var Save */
    protected $save;

    /** @var PropagateProperties */
    private $propagateProperties;

    public function let(Delegates\ForeignEntityDelegate $foreignEntityDelegate, Save $save, PropagateProperties $propagateProperties)
    {
        $this->beConstructedWith($foreignEntityDelegate, null, null, null, $save, $propagateProperties);
        $this->foreignEntityDelegate = $foreignEntityDelegate;

        $this->save = $save;
        $this->propagateProperties = $propagateProperties;

        Session::setUser((new User())->set('guid', 123)->set('username', 'test'));
    }

    public function letGo()
    {
        Session::setUser(null);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_update_an_activity()
    {
        $this->save->setEntity(Argument::that(function ($activity) {
            return $activity->getMessage() === 'hello world';
        }))
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled();

        $activity = new Activity();
        $activity->owner_guid = 123;

        $activityMutation = new EntityMutation($activity);

        $activityMutation->setMessage('hello world');
        $activityMutation->setTitle('hello title');
        $activityMutation->setEntityGuid("456");
        $activityMutation->setMature(false);
        $activityMutation->setTags([ 'music', 'technology' ]);
        $activityMutation->setNsfw([1]);
   
        $activityMutation->setWireThreshold(['type' => 'tokens']);
        $activityMutation->setPaywall(true);
    
        $activityMutation->setLicense('');
    
        $activityMutation->setTimeCreated(time());
    
        $activityMutation
            ->setBlurb('blurb')
            ->setURL('url')
            ->setThumbnail('thumbnail');

        $this->update($activityMutation);
    }

    public function it_should_update_a_video()
    {
        $this->foreignEntityDelegate->onUpdate(Argument::type(Activity::class), Argument::type(EntityMutation::class));

        $activity = new Activity();
        $activity->owner_guid = 123;
        $activity->type = 'object';
        $activity->subtype = 'video';

        $activityMutation = new EntityMutation($activity);

        $this->update($activityMutation);
    }
}
