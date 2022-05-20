<?php

namespace Spec\Minds\Core\Recommendations\Algorithms\FriendsOfFriend;

use Minds\Core\Recommendations\Algorithms\FriendsOfFriend\Repository;
use Minds\Core\Security\Block\Manager as BlockManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\Video;
use Minds\Core\Security\Block;

class RepositorySpec extends ObjectBehavior
{
    /** @var ElasticSearchClient */
    protected $elasticsearch;

    /** @var BlockManager */
    protected $blockManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function let(
        ElasticSearchClient $elasticsearch,
        BlockManager $blockManager,
        EntitiesBuilder $entitiesBuilder,
    ) {
        $this->elasticsearch = $elasticsearch;
        $this->blockManager = $blockManager;
        $this->entitiesBuilder = $entitiesBuilder;

        $this->beConstructedWith($elasticsearch, null, null, null, $blockManager, $entitiesBuilder);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_filter_out_channels_that_have_blocked_us(Activity $activity, Video $video)
    {
        $targetGuid = '1232323';
        $buckets = $this->generateFakeBuckets();
        $this->elasticsearch->request(Argument::any())->willReturn([
            'aggregations' => [
                'channels' => [
                    'buckets' => $buckets
                ]
            ]
        ]);

        $this->entitiesBuilder->single(Argument::any())
            ->shouldBeCalled()
            ->willReturn((new Activity)
            ->set('guid', 123));

        $blockEntry = (new Block\BlockEntry())
            ->setActorGuid($targetGuid)
            ->setSubjectGuid($buckets[0]['key']);

        $this->blockManager->isBlocked($blockEntry)->shouldBeCalled()->willReturn(true);
        $this->blockManager->isBlocked(Argument::any())->shouldBeCalled()->willReturn(false);
        $this->blockManager->hasBlocked(Argument::any())->shouldBeCalled()->willReturn(false);
        
        $this->getList([
            'targetUserGuid' => $targetGuid,
            'currentChannelUserGuid' => '1232323',
            'limit' => 12,
            'mostRecentSubscriptionUserGuid' => '123333',
        ])->shouldHaveCount(9);
    }

    public function it_should_filter_out_channels_we_have_blocked()
    {
        $targetGuid = '1232323';
        $buckets = $this->generateFakeBuckets();
        $this->elasticsearch->request(Argument::any())->willReturn([
            'aggregations' => [
                'channels' => [
                    'buckets' => $buckets
                ]
            ]
        ]);

        $this->entitiesBuilder->single(Argument::any())
            ->shouldBeCalled()
            ->willReturn((new Activity)
            ->set('guid', 123));

        $blockEntry = (new Block\BlockEntry())
            ->setActorGuid($targetGuid)
            ->setSubjectGuid($buckets[0]['key']);

        $this->blockManager->hasBlocked($blockEntry)->shouldBeCalled()->willReturn(true);
        $this->blockManager->isBlocked(Argument::any())->shouldBeCalled()->willReturn(false);
        $this->blockManager->hasBlocked(Argument::any())->shouldBeCalled()->willReturn(false);

        $this->getList([
            'targetUserGuid' => $targetGuid,
            'currentChannelUserGuid' => '1232323',
            'limit' => 12,
            'mostRecentSubscriptionUserGuid' => '123333',
        ])->shouldHaveCount(9);
    }

    public function it_should_hydrate_the_top_12()
    {
        $targetGuid = '1232323';
        $buckets = $this->generateFakeBuckets(20);
        $this->elasticsearch->request(Argument::any())->willReturn([
            'aggregations' => [
                'channels' => [
                    'buckets' => $buckets
                ]
            ]
        ]);

        $this->entitiesBuilder->single(Argument::any())
            ->shouldBeCalledTimes(12)
            ->willReturn((new Activity)
            ->set('guid', 123));

        $this->blockManager->isBlocked(Argument::any())->shouldBeCalled()->willReturn(false);
        $this->blockManager->hasBlocked(Argument::any())->shouldBeCalled()->willReturn(false);

        $this->getList([
            'targetUserGuid' => $targetGuid,
            'currentChannelUserGuid' => '1232323',
            'limit' => 12,
            'mostRecentSubscriptionUserGuid' => '123333',
        ])->shouldHaveCount(20);
    }

    public function it_should_hydrate_the_top_12_even_if_some_were_blocked()
    {
        $targetGuid = '1232323';
        $buckets = $this->generateFakeBuckets(20);
        $this->elasticsearch->request(Argument::any())->willReturn([
            'aggregations' => [
                'channels' => [
                    'buckets' => $buckets
                ]
            ]
        ]);

        $this->entitiesBuilder->single(Argument::any())
            ->shouldBeCalledTimes(12)
            ->willReturn((new Activity)
            ->set('guid', 123));

        $blockEntry = (new Block\BlockEntry())
            ->setActorGuid($targetGuid)
            ->setSubjectGuid($buckets[0]['key']);
        $blockEntry2 = (new Block\BlockEntry())
            ->setActorGuid($targetGuid)
            ->setSubjectGuid($buckets[1]['key']);

        $this->blockManager->isBlocked($blockEntry)->shouldBeCalled()->willReturn(true);
        $this->blockManager->isBlocked($blockEntry2)->shouldBeCalled()->willReturn(true);
        $this->blockManager->isBlocked(Argument::any())->shouldBeCalled()->willReturn(false);
        $this->blockManager->hasBlocked(Argument::any())->shouldBeCalled()->willReturn(false);

        $this->getList([
            'targetUserGuid' => $targetGuid,
            'currentChannelUserGuid' => '1232323',
            'limit' => 12,
            'mostRecentSubscriptionUserGuid' => '123333',
        ])->shouldHaveCount(18);
    }

    private function generateFakeBuckets($num = 10)
    {
        $buckets = [];

        for ($i = 0; $i < $num; $i++) {
            $buckets[] = [
                'doc_count' => 1,
                'key' => $i,
            ];
        }

        return $buckets;
    }
}
