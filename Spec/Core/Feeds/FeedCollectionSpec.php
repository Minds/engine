<?php

namespace Spec\Minds\Core\Feeds;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Clock;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic\Entities as ElasticEntities;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Core\Feeds\FeedCollection;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Entities\Entity;
use Minds\Entities\User;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FeedCollectionSpec extends ObjectBehavior
{
    /** @var ElasticManager */
    protected $elasticManager;

    /** @var ElasticEntities */
    protected $elasticEntities;

    /** @var UserHashtagsManager */
    protected $userHashtagsManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Clock */
    protected $clock;

    public function let(
        ElasticManager $elasticManager,
        ElasticEntities $elasticEntities,
        UserHashtagsManager $userHashtagsManager,
        EntitiesBuilder $entitiesBuilder,
        Clock $clock
    ) {
        $this->elasticManager = $elasticManager;
        $this->elasticEntities = $elasticEntities;
        $this->userHashtagsManager = $userHashtagsManager;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->clock = $clock;

        $this->beConstructedWith(
            $elasticManager,
            $elasticEntities,
            $userHashtagsManager,
            $entitiesBuilder,
            $clock
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FeedCollection::class);
    }

    public function it_should_fetch_with_sync_on(
        Response $elasticManagerResponse,
        FeedSyncEntity $feedSyncEntity1,
        FeedSyncEntity $feedSyncEntity2
    )
    {
        $this->elasticManager->getList([
            'cache_key' => null,
            'container_guid' => null,
            'access_id' => null,
            'custom_type' => '',
            'limit' => 12,
            'offset' => 0,
            'type' => 'activity',
            'algorithm' => 'top',
            'period' => '12h',
            'sync' => true,
            'query' => '',
            'single_owner_threshold' => 0,
            'as_activities' => false,
            'nsfw' => [],
            'hashtags' => null,
            'filter_hashtags' => true
        ])
            ->shouldBeCalled()
            ->willReturn($elasticManagerResponse);

        $elasticManagerResponse->toArray()
            ->shouldBeCalled()
            ->willReturn([$feedSyncEntity1, $feedSyncEntity2]);

        /** @var FeedCollection $this */
        $this
            ->setSync(true)
            ->setFilter('global')
            ->setAlgorithm('top')
            ->setType('activity')
            ->setPeriod('12h')
            ->fetch()
            ->shouldBeAResponse([$feedSyncEntity1, $feedSyncEntity2]);
    }

    public function it_should_fetch_with_sync_off_and_entity_filtering(
        User $actor,
        Response $elasticManagerResponse,
        Entity $entity1,
        Entity $entity2
    ) {
        $actor->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->elasticManager->getList([
            'cache_key' => '1000',
            'container_guid' => null,
            'access_id' => null,
            'custom_type' => '',
            'limit' => 12,
            'offset' => 0,
            'type' => 'activity',
            'algorithm' => 'top',
            'period' => '12h',
            'sync' => false,
            'query' => '',
            'single_owner_threshold' => 0,
            'as_activities' => false,
            'nsfw' => [],
            'hashtags' => null,
            'filter_hashtags' => true
        ])
            ->shouldBeCalled()
            ->willReturn($elasticManagerResponse);

        $elasticManagerResponse->toArray()
            ->shouldBeCalled()
            ->willReturn([$entity1, $entity2]);

        $this->elasticEntities->setActor($actor)
            ->shouldBeCalled()
            ->willReturn($this->elasticEntities);

        $this->elasticEntities->filter($entity1, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(false);

        $this->elasticEntities->filter($entity2, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(true);

        /** @var FeedCollection $this */
        $this
            ->setActor($actor)
            ->setSync(false)
            ->setFilter('global')
            ->setAlgorithm('top')
            ->setType('activity')
            ->setPeriod('12h')
            ->fetch()
            ->shouldBeAResponse([$entity2]);
    }

    public function it_should_fetch_with_sync_on_using_offset_limit_and_cap(
        Response $elasticManagerResponse,
        FeedSyncEntity $feedSyncEntity
    )
    {
        $baseOpts = [
            'cache_key' => null,
            'container_guid' => null,
            'access_id' => null,
            'custom_type' => '',
            'type' => 'activity',
            'algorithm' => 'top',
            'period' => '12h',
            'sync' => true,
            'query' => '',
            'single_owner_threshold' => 0,
            'as_activities' => false,
            'nsfw' => [],
            'hashtags' => null,
            'filter_hashtags' => true
        ];

        $this->elasticManager->getList(array_merge($baseOpts, [
            'limit' => 2,
            'offset' => 0,
        ]))
            ->shouldBeCalled()
            ->willReturn($elasticManagerResponse);

        $this->elasticManager->getList(array_merge($baseOpts, [
            'limit' => 2,
            'offset' => 2,
        ]))
            ->shouldBeCalled()
            ->willReturn($elasticManagerResponse);

        $this->elasticManager->getList(array_merge($baseOpts, [
            'limit' => 2,
            'offset' => 4,
        ]))
            ->shouldNotBeCalled();

        $elasticManagerResponse->toArray()
            ->shouldBeCalled()
            ->willReturn([$feedSyncEntity]);

        /** @var FeedCollection $this */
        $this
            ->setSync(true)
            ->setFilter('global')
            ->setAlgorithm('top')
            ->setType('activity')
            ->setPeriod('12h')
            ->setLimit(2)
            ->setOffset(0)
            ->setCap(3)
            ->fetch()
            ->shouldBeAResponse([$feedSyncEntity], '2');

        /** @var FeedCollection $this */
        $this
            ->setSync(true)
            ->setFilter('global')
            ->setAlgorithm('top')
            ->setType('activity')
            ->setPeriod('12h')
            ->setLimit(2)
            ->setOffset(2)
            ->setCap(3)
            ->fetch()
            ->shouldBeAResponse([$feedSyncEntity], '4');

        /** @var FeedCollection $this */
        $this
            ->setSync(true)
            ->setFilter('global')
            ->setAlgorithm('top')
            ->setType('activity')
            ->setPeriod('12h')
            ->setLimit(2)
            ->setOffset(4)
            ->setCap(3)
            ->fetch()
            ->shouldBeAResponse([], '3', [ 'overflow' => true ]);
    }

    public function it_should_fetch_with_sync_on_by_all_or_filtering_by_hashtag(
        Response $elasticManagerResponse,
        FeedSyncEntity $feedSyncEntity
    )
    {
        $baseOpts = [
            'cache_key' => null,
            'container_guid' => null,
            'access_id' => null,
            'custom_type' => '',
            'limit' => 12,
            'offset' => 0,
            'type' => 'activity',
            'algorithm' => 'top',
            'period' => '12h',
            'sync' => true,
            'query' => '',
            'single_owner_threshold' => 0,
            'as_activities' => false,
            'nsfw' => [],
            'filter_hashtags' => true
        ];

        $this->elasticManager->getList(array_merge($baseOpts, [
            'hashtags' => [],
        ]))
            ->shouldBeCalledTimes(1)
            ->willReturn($elasticManagerResponse);

        $this->elasticManager->getList(array_merge($baseOpts, [
            'hashtags' => ['phpspec'],
        ]))
            ->shouldBeCalledTimes(1)
            ->willReturn($elasticManagerResponse);

        $elasticManagerResponse->toArray()
            ->shouldBeCalled()
            ->willReturn([$feedSyncEntity]);

        /** @var FeedCollection $this */
        $this
            ->setSync(true)
            ->setFilter('global')
            ->setAlgorithm('top')
            ->setType('activity')
            ->setPeriod('12h')
            ->setAll(true)
            ->setHashtags([])
            ->fetch()
            ->shouldBeAResponse([$feedSyncEntity]);

        /** @var FeedCollection $this */
        $this
            ->setSync(true)
            ->setFilter('global')
            ->setAlgorithm('top')
            ->setType('activity')
            ->setPeriod('12h')
            ->setAll(false)
            ->setHashtags(['phpspec'])
            ->fetch()
            ->shouldBeAResponse([$feedSyncEntity]);
    }

    public function it_should_fetch_filtering_by_preferred_hashtags(
        User $actor,
        Response $elasticManagerResponse,
        FeedSyncEntity $feedSyncEntity
    ) {
        $actor->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->userHashtagsManager->setUser($actor)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->values([
            'limit' => 50,
            'trending' => false,
            'defaults' => false,
        ])
            ->shouldBeCalled()
            ->willReturn(['phpspec1', 'phpspec2']);

        $this->elasticManager->getList([
            'cache_key' => '1000',
            'container_guid' => null,
            'access_id' => null,
            'custom_type' => '',
            'limit' => 12,
            'offset' => 0,
            'type' => 'activity',
            'algorithm' => 'top',
            'period' => '12h',
            'sync' => true,
            'query' => '',
            'single_owner_threshold' => 0,
            'as_activities' => false,
            'nsfw' => [],
            'hashtags' => ['phpspec1', 'phpspec2'],
            'filter_hashtags' => false
        ])
            ->shouldBeCalled()
            ->willReturn($elasticManagerResponse);

        $elasticManagerResponse->toArray()
            ->shouldBeCalled()
            ->willReturn([$feedSyncEntity]);

        /** @var FeedCollection $this */
        $this
            ->setActor($actor)
            ->setSync(true)
            ->setFilter('global')
            ->setAlgorithm('top')
            ->setType('activity')
            ->setPeriod('12h')
            ->setAll(true)
            ->setHashtags(null)
            ->setAll(false)
            ->fetch()
            ->shouldBeAResponse([$feedSyncEntity]);
    }

    public function it_should_throw_if_no_filter_during_fetch()
    {
        /** @var FeedCollection $this */
        $this
            ->setFilter('')
            ->setAlgorithm('top')
            ->setType('activity')
            ->setPeriod('12h')
            ->shouldThrow(new Exception('Missing filter'))
            ->duringFetch();
    }

    public function it_should_throw_if_no_algorithm_during_fetch()
    {
        /** @var FeedCollection $this */
        $this
            ->setFilter('global')
            ->setAlgorithm('')
            ->setType('activity')
            ->setPeriod('12h')
            ->shouldThrow(new Exception('Missing algorithm'))
            ->duringFetch();
    }

    public function it_should_throw_if_no_type_during_fetch()
    {
        /** @var FeedCollection $this */
        $this
            ->setFilter('global')
            ->setAlgorithm('top')
            ->setType('')
            ->setPeriod('12h')
            ->shouldThrow(new Exception('Missing type'))
            ->duringFetch();
    }

    public function it_should_throw_if_no_period_during_fetch()
    {
        /** @var FeedCollection $this */
        $this
            ->setFilter('global')
            ->setAlgorithm('top')
            ->setType('activity')
            ->setPeriod('')
            ->shouldThrow(new Exception('Missing period'))
            ->duringFetch();
    }

    public function getMatchers(): array
    {
        $matchers = [];

        $matchers['beAResponse'] = function ($subject, $elements = null, $pagingToken = null, $attributes = null) {
            if (!($subject instanceof Response)) {
                throw new FailureException("Subject should be a Response");
            }

            if ($elements !== null && $elements !== $subject->toArray()) {
                throw new FailureException("Subject elements don't match");
            }

            if ($pagingToken !== null && $pagingToken !== $subject->getPagingToken()) {
                throw new FailureException("Subject paging token doesn't match");
            }

            if ($attributes !== null && $attributes !== $subject->getAttributes()) {
                throw new FailureException("Subject attributes don't match");
            }

            return true;
        };

        return $matchers;
    }
}
