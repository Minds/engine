<?php

namespace Spec\Minds\Core\Feeds\Activity;

use Minds\Core\Feeds\Activity\Manager;
use Minds\Common\EntityMutation;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\PropagateProperties;
use Minds\Core\Boost\Network\ElasticRepository as BoostElasticRepository;
use Minds\Core\Entities\GuidLinkResolver;
use Minds\Core\Session;
use Minds\Core\Feeds\Activity\Delegates;
use Minds\Exceptions\UserErrorException;
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

    /** @var Delegates\PaywallDelegate */
    private $paywallDelegate;

    /** @var Delegates\MetricsDelegate */
    private $metricsDelegate;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var BoostElasticRepository */
    private $boostRepository;

    /** @var GuidLinkResolver */
    private $guidLinkResolver;

    public function let(
        Delegates\ForeignEntityDelegate $foreignEntityDelegate,
        Save $save,
        PropagateProperties $propagateProperties,
        Delegates\PaywallDelegate $paywallDelegate,
        Delegates\MetricsDelegate $metricsDelegate,
        Delegates\NotificationsDelegate $notificationsDelegate,
        EntitiesBuilder $entitiesBuilder,
        BoostElasticRepository $boostRepository,
        GuidLinkResolver $guidLinkResolver
    ) {
        $this->beConstructedWith(
            $foreignEntityDelegate,
            null,
            null,
            null,
            $save,
            null,
            $propagateProperties,
            null,
            $paywallDelegate,
            $metricsDelegate,
            $notificationsDelegate,
            $entitiesBuilder,
            null,
            null,
            $boostRepository,
            $guidLinkResolver
        );
        $this->foreignEntityDelegate = $foreignEntityDelegate;

        $this->save = $save;
        $this->propagateProperties = $propagateProperties;
        $this->paywallDelegate = $paywallDelegate;
        $this->metricsDelegate = $metricsDelegate;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->boostRepository = $boostRepository;
        $this->guidLinkResolver = $guidLinkResolver;

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

    public function it_should_add_an_activity()
    {
        $activity = new Activity();
        $activity->guid = 123;

        $this->save->setEntity(Argument::that(function ($activity) {
            return $activity->getGuid() === '123';
        }))
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled()
            ->willReturn(true);

        //

        $this->add($activity)->shouldBe(true);
    }

    public function it_should_apply_remind_delegates_and_nsfw(Activity $activity)
    {
        $this->save->setEntity(Argument::that(function ($activity) {
            return $activity->getGuid() === '123';
        }))
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $activity->getMessage()
            ->willReturn('Hello World');

        $activity->getTitle()
            ->willReturn('');

        $activity->getGuid()
            ->willReturn(123);

        $activity->isRemind()
            ->willReturn(true);

        $activity->getRemind()
            ->willReturn((new Activity)->setNsfw([1]));

        $activity->getNsfw()
            ->willReturn([2]);

        $activity->setNsfw([1, 2])
            ->willReturn($activity);

        //

        $this->add($activity)->shouldBe(true);
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

        $this->boostRepository->getList([
            'entity_guid' => null,
            'state' => 'approved'
        ])->shouldBeCalled()
            ->willReturn([]);
    
        $activity = new Activity();
        $activity->owner_guid = 123;

        $activityMutation = new EntityMutation($activity);

        $activityMutation->setMessage('hello world');
        $activityMutation->setTitle('hello title');
        $activityMutation->setEntityGuid("456");
        $activityMutation->setMature(false);
        $activityMutation->setTags(['music', 'technology']);
        $activityMutation->setNsfw([1]);

        $activityMutation->setWireThreshold(['type' => 'tokens', 'min' => 1]);
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

        $this->boostRepository->getList([
            'entity_guid' => null,
            'state' => 'approved'
        ])->shouldBeCalled()
            ->willReturn([]);

        $activity = new Activity();
        $activity->owner_guid = 123;
        $activity->type = 'object';
        $activity->subtype = 'video';

        $activityMutation = new EntityMutation($activity);

        $this->update($activityMutation);
    }

    public function it_should_update_an_activity_wire_threshold()
    {
        $this->paywallDelegate->onUpdate(Argument::any())
            ->shouldBeCalled();

        $this->save->setEntity(Argument::that(function ($activity) {
            return $activity->getWireThreshold() === [
                'support_tier' => [
                    'urn' => 'urn:support-tier:spec-test/x'
                ]
            ];
        }))
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled();

        $this->boostRepository->getList([
                'entity_guid' => null,
                'state' => 'approved'
            ])->shouldBeCalled()
                ->willReturn([]);

        $activity = new Activity();
        $activity->owner_guid = 123;

        $activityMutation = new EntityMutation($activity);

        $activityMutation->setWireThreshold([
            'support_tier' => [
                'urn' => 'urn:support-tier:spec-test/x',
            ]
        ]);
        $activityMutation->setPaywall(true);

        $this->update($activityMutation);
    }

    public function it_should_not_update_an_actively_boosted_activity(
        EntityMutation $activityMutation
    ) {
        $activity = new Activity();
        $activity['guid'] = 123;

        $activityMutation->getMutatedEntity()
            ->shouldBeCalled()
            ->willReturn($activity);

        $activityMutation->getOriginalEntity()
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->guidLinkResolver->resolve(123)
            ->shouldBeCalled()
            ->willReturn(321);

        $this->boostRepository->getList([
            'entity_guid' => [123, 321],
            'state' => 'approved'
        ])->shouldBeCalled()
            ->willReturn([
                1
            ]);

        $this->shouldThrow(UserErrorException::class)
            ->during("update", [$activityMutation]);
    }

    public function it_should_not_update_an_actively_boosted_activity_with_a_linked_entity(
        EntityMutation $activityMutation
    ) {
        $activity = new Activity();

        $activity->guid = 123;
        $activity->entity_guid = 321;

        $activityMutation->getMutatedEntity()
            ->shouldBeCalled()
            ->willReturn($activity);

        $activityMutation->getOriginalEntity()
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->guidLinkResolver->resolve(123)
            ->shouldNotBeCalled();

        $this->boostRepository->getList([
            'entity_guid' => [123, 321],
            'state' => 'approved'
        ])->shouldBeCalled()
            ->willReturn([
                1
            ]);

        $this->shouldThrow(UserErrorException::class)
            ->during("update", [$activityMutation]);
    }
}
