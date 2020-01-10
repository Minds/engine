<?php

namespace Spec\Minds\Core\Feeds\Firehose;

use Minds\Core\Entities\PropagateProperties;
use PhpSpec\ObjectBehavior;
use Minds\Entities\User;
use Minds\Core\Feeds\Firehose\Manager;
use Minds\Common\Repository\Response;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Core\Blogs\Blog;
use Minds\Entities\Image;
use Minds\Core\Feeds\Elastic\Manager as TopFeedsManager;
use Minds\Core\Feeds\Firehose\ModerationCache;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Data\Call;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Feeds\FeedSyncEntity;

class ManagerSpec extends ObjectBehavior
{
    /** @var User */
    protected $user;
    /** @var TopFeedsManager */
    protected $topFeedsManager;
    /** @var ModerationCache */
    protected $moderationCache;
    /** @var Save */
    protected $save;
    /** @var PropagateProperties */
    protected $propagateProperties;

    protected $guids = [
        '968599624820461570', '966142563226488850', '966145446911152135',
        '966146759803801618', '968594045251096596', '966031787253829640',
        '966032235331325967', '966030585254383635', '966020677003907088',
        '966042003450105868',
    ];

    public function let(
        User $user,
        TopFeedsManager $topFeedsManager,
        ModerationCache $moderationCache,
        Save $save,
        PropagateProperties $propagateProperties
    ) {
        $this->user = $user;
        $this->topFeedsManager = $topFeedsManager;
        $this->moderationCache = $moderationCache;
        $this->save = $save;
        $this->propagateProperties = $propagateProperties;

        $this->user->getGUID()->willReturn(123);

        $this->beConstructedWith(
            $this->topFeedsManager,
            $this->moderationCache,
            $this->save,
            $this->propagateProperties
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_results()
    {
        $activities = $this->getMockActivities();
        /** @var Response $response */
        $response = new Response($activities);

        $this->topFeedsManager->getList([
            'moderation_user' => $this->user,
            'exclude_moderated' => true,
            'moderation_reservations' => null,
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->moderationCache->getKeysLockedByOtherUsers($this->user)
            ->shouldBeCalled();

        $this->moderationCache->store('123', $this->user)
            ->shouldBeCalled();

        $this->moderationCache->store('456', $this->user)
            ->shouldBeCalled();

        $this->getList([
            'moderation_user' => $this->user,
        ])->shouldBeLike($response->map(function ($entity) {
            return $entity->getEntity();
        }));
    }

    public function it_should_return_results_without_a_user()
    {
        $activities = $this->getMockActivities();
        /** @var Response $reponse */
        $response = new Response($activities);
        $activities = $this->getMockActivities($activities);

        $this->topFeedsManager->getList([
            'moderation_user' => null,
            'exclude_moderated' => true,
            'moderation_reservations' => null,
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->getList()->shouldBeLike($response->map(function ($entity) {
            return $entity->getEntity();
        }));
    }

    public function it_should_save_and_propogate(Entity $activity)
    {
        $time = time();
        $activity->setTimeModerated($time)->shouldBeCalled();
        $activity->setModeratorGuid(123)->shouldBeCalled();
        $this->save->setEntity($activity)->shouldBeCalled()->willReturn($this->save);
        $this->save->save()->shouldBeCalled();
        $this->propagateProperties->from($activity)->shouldBeCalled();
        $this->save($activity, $this->user, $time);
    }

    private function getMockActivities(bool $moderated = false)
    {
        $entities = [];

        $entity = new FeedSyncEntity();
        $activity = new Activity();
        $activity->guid = 123;
        $entities[] = $entity->setEntity($activity);

        $entity = new FeedSyncEntity();
        $activity = new Activity();
        $activity->guid = 456;
        $entities[] = $entity->setEntity($activity);

        return $entities;
    }
}
