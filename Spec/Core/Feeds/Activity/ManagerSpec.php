<?php

namespace Spec\Minds\Core\Feeds\Activity;

use Minds\Core\Feeds\Activity\Manager;
use Minds\Common\EntityMutation;
use Minds\Entities\Activity;
use Minds\Core\Blogs\Blog;
use Minds\Entities\User;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\PropagateProperties;
use Minds\Core\Session;
use Minds\Core\Feeds\Activity\Delegates;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Exceptions\RbacNotAllowed;
use Minds\Core\Security\Rbac\Services\RbacGatekeeperService;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Counters;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use InvalidArgumentException;
use Minds\Core\Feeds\Elastic\V2\Manager as ElasticV2Manager;
use Minds\Core\Media\Audio\AudioService;

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

    /** @var ElasticManager */
    private $elasticManager;

    /** @var Counters */
    private $counters;

    private Collaborator $rbacGatekeeperServiceMock;

    /** @var ElasticV2Manager */
    private $elasticV2Manager;

    public function let(
        Delegates\ForeignEntityDelegate $foreignEntityDelegate,
        Save $save,
        PropagateProperties $propagateProperties,
        Delegates\PaywallDelegate $paywallDelegate,
        Delegates\MetricsDelegate $metricsDelegate,
        Delegates\NotificationsDelegate $notificationsDelegate,
        EntitiesBuilder $entitiesBuilder,
        ElasticManager $elasticManager,
        RbacGatekeeperService $rbacGatekeeperServiceMock,
        Counters $counters,
        ElasticV2Manager $elasticV2Manager,
        AudioService $audioService,
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
            $elasticManager,
            $rbacGatekeeperServiceMock,
            $counters,
            $elasticV2Manager,
            $audioService,
        );
        $this->foreignEntityDelegate = $foreignEntityDelegate;

        $this->save = $save;
        $this->propagateProperties = $propagateProperties;
        $this->paywallDelegate = $paywallDelegate;
        $this->metricsDelegate = $metricsDelegate;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->elasticManager = $elasticManager;
        $this->rbacGatekeeperServiceMock = $rbacGatekeeperServiceMock;
        $this->counters = $counters;
        $this->elasticV2Manager = $elasticV2Manager;

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
        $activity->message = 'hello world';

        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_CREATE_POST)->willReturn(true);

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

    public function it_should_add_an_activity_with_no_message_but_a_title()
    {
        $activity = new Activity();
        $activity->guid = 123;
        $activity->title = 'hello world';
        $activity->message = null;

        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_CREATE_POST)->willReturn(true);

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

    public function it_should_add_an_activity_with_no_message_or_title_but_a_thumbnail()
    {
        $activity = new Activity();
        $activity->guid = 123;
        $activity->title = null;
        $activity->message = null;
        $activity->thumbnail_src = '~thumbnail~';

        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_CREATE_POST)->willReturn(true);

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
        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_INTERACT)->willReturn(true);

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

        $this->save->withMutatedAttributes(Argument::that(function ($mutatedAttributes) {
            $success = true;
            foreach ($mutatedAttributes as $attr) {
                if (!in_array($attr, [
                        'message',
                        'title',
                        'entity_guid',
                        'tags',
                        'blurb',
                        'perma_url',
                        'thumbnail_src',
                        'time_created',
                        'wire_threshold',
                        'paywall',
                        'nsfw',
                    ], true)) {
                    var_dump($attr);
                    $success = false;
                    error_log('Not found attribute ' . $attr);
                }
            }
            return $success;
        }))
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled()
            ->willReturn(true);

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

        $activity = new Activity();
        $activity->owner_guid = 123;
        $activity->type = 'object';
        $activity->subtype = 'video';
        $activity->message = 'hello world';

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

        $this->save->withMutatedAttributes([
                'wire_threshold',
                'paywall',
            ])
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $activity = new Activity();
        $activity->owner_guid = 123;
        $activity->message = 'hello world';
        $activityMutation = new EntityMutation($activity);

        $activityMutation->setWireThreshold([
            'support_tier' => [
                'urn' => 'urn:support-tier:spec-test/x',
            ]
        ]);
        $activityMutation->setPaywall(true);

        $this->update($activityMutation);
    }

    public function it_should_not_add_an_activity_that_has_no_attachments_or_message()
    {
        $activity = new Activity();
        $activity->guid = 123;
        $activity->message = null;
        $activity->title = null;
        $activity->thumbnail_src = '';
        $activity->attachments = null;

        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_CREATE_POST)->willReturn(true);

        $this->save->setEntity(Argument::any())
            ->shouldNotBeCalled();

        $this->save->save()
            ->shouldNotBeCalled();

        //

        $this->shouldThrow(new UserErrorException(
            'Activities must have either attachments, a thumbnail or a message'
        ))->during('add', [ $activity ]);
    }

    public function it_should_not_allow_created_post_if_no_permission()
    {
        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_CREATE_POST)->willThrow(new RbacNotAllowed(PermissionsEnum::CAN_CREATE_POST));

        $activity = new Activity();
        $this->shouldThrow(RbacNotAllowed::class)->duringAdd($activity);
    }

    public function it_counts_reminds_of_activity_by_user(Activity $activity, User $user)
    {
        $activityGuid = '123';
        $entityGuid = '456';
        $userGuid = '789';

        $activity->getGuid()->willReturn($activityGuid);
        $activity->getEntityGuid()->willReturn($entityGuid);
        $user->getGuid()->willReturn($userGuid);

        $this->elasticManager->getCount([
            'algorithm' => 'latest',
            'type' => 'activity',
            'period' => 'all',
            'remind_guid' => $activityGuid,
            'owner_guid' => $userGuid,
        ])->willReturn(5);

        $this->elasticManager->getCount([
            'algorithm' => 'latest',
            'type' => 'activity',
            'period' => 'all',
            'remind_guid' => $entityGuid,
            'owner_guid' => $userGuid,
        ])->willReturn(3);

        $this->countRemindsOfEntityByUser($activity, $user)->shouldReturn(8);
    }

    public function it_counts_reminds_of_blog_by_user(Blog $blog, User $user, Activity $linkedActivity)
    {
        $blogGuid = '123';
        $userGuid = '789';
        $linkedActivityGuid = '456';

        $blog->getGuid()->willReturn($blogGuid);
        $user->getGuid()->willReturn($userGuid);

        $this->counters->get($blogGuid, 'remind')
            ->shouldBeCalled()
            ->willReturn(1);


        $this->elasticV2Manager->getLinkedActivitiesByEntityGuid($blogGuid)->willReturn([$linkedActivity]);

        $linkedActivity->getGuid()->willReturn($linkedActivityGuid);

        $linkedActivity->getEntityGuid()->willReturn($blogGuid);
        $user->getGuid()->willReturn($userGuid);

        $this->elasticManager->getCount([
            'algorithm' => 'latest',
            'type' => 'activity',
            'period' => 'all',
            'remind_guid' => $linkedActivityGuid,
            'owner_guid' => $userGuid,
        ])->shouldBeCalled()->willReturn(1);

        $this->elasticManager->getCount([
            'algorithm' => 'latest',
            'type' => 'activity',
            'period' => 'all',
            'remind_guid' => $blogGuid,
            'owner_guid' => $userGuid,
        ])->shouldBeCalled()->willReturn(1);

        $this->countRemindsOfEntityByUser($blog, $user)->shouldReturn(1 + 1 + 1);

    }
}
