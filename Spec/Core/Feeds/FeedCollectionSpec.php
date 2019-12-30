<?php

namespace Spec\Minds\Core\Feeds;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Clock;
use Minds\Core\Feeds\Elastic\Entities as ElasticEntities;
use Minds\Core\Feeds\Elastic\Manager as ElasticManager;
use Minds\Core\Feeds\FeedCollection;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
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

    /** @var Clock */
    protected $clock;

    /**
     * FeedCollection constructor.
     * @param ElasticManager $elasticManager
     * @param ElasticEntities $elasticEntities
     * @param UserHashtagsManager $userHashtagsManager
     * @param Clock $clock
     */
    public function let(
        ElasticManager $elasticManager,
        ElasticEntities $elasticEntities,
        UserHashtagsManager $userHashtagsManager,
        Clock $clock
    ) {
        $this->elasticManager = $elasticManager;
        $this->elasticEntities = $elasticEntities;
        $this->userHashtagsManager = $userHashtagsManager;
        $this->clock = $clock;

        $this->beConstructedWith(
            $elasticManager,
            $elasticEntities,
            $userHashtagsManager,
            $clock
        );
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(FeedCollection::class);
    }

    public function it_should_fetch()
    {
        // /** @var FeedCollection $this */
        // $this
        //     ->setFilter('global')
        //     ->setAlgorithm('top')
        //     ->setType('activity')
        //     ->setPeriod('12h')
        //     ->fetch()
        //     ->shouldBeAResponse([]);
    }

    public function it_should_fetch_with_acl_restrictions(
        User $actor
    ) {
        // /** @var FeedCollection $this */
        // $this
        //     ->setActor($actor)
        //     ->setFilter('global')
        //     ->setAlgorithm('top')
        //     ->setType('activity')
        //     ->setPeriod('12h')
        //     ->fetch()
        //     ->shouldBeAResponse([]);
    }

    public function it_should_fetch_using_offset_limit_and_cap()
    {
        // /** @var FeedCollection $this */
        // $this
        //     ->setFilter('global')
        //     ->setAlgorithm('top')
        //     ->setType('activity')
        //     ->setPeriod('12h')
        //     ->setLimit(2)
        //     ->setOffset(0)
        //     ->setCap(3)
        //     ->fetch()
        //     ->shouldBeAResponse([]);
        //
        // /** @var FeedCollection $this */
        // $this
        //     ->setFilter('global')
        //     ->setAlgorithm('top')
        //     ->setType('activity')
        //     ->setPeriod('12h')
        //     ->setLimit(2)
        //     ->setOffset(2)
        //     ->setCap(3)
        //     ->fetch()
        //     ->shouldBeAResponse([]);
        //
        // /** @var FeedCollection $this */
        // $this
        //     ->setFilter('global')
        //     ->setAlgorithm('top')
        //     ->setType('activity')
        //     ->setPeriod('12h')
        //     ->setLimit(2)
        //     ->setOffset(4)
        //     ->setCap(3)
        //     ->fetch()
        //     ->shouldBeAResponse([], null, [ 'overflow' => true ]);
    }

    public function it_should_fetch_all_or_filtering_by_hashtag()
    {
        // /** @var FeedCollection $this */
        // $this
        //     ->setFilter('global')
        //     ->setAlgorithm('top')
        //     ->setType('activity')
        //     ->setPeriod('12h')
        //     ->setAll(true)
        //     ->setHashtags([])
        //     ->fetch()
        //     ->shouldBeAResponse([]);
        //
        // /** @var FeedCollection $this */
        // $this
        //     ->setFilter('global')
        //     ->setAlgorithm('top')
        //     ->setType('activity')
        //     ->setPeriod('12h')
        //     ->setAll(false)
        //     ->setHashtags(['phpspec'])
        //     ->fetch()
        //     ->shouldBeAResponse([]);
    }

    public function it_should_fetch_filtering_by_preferred_hashtags(
        User $user
    ) {
        // /** @var FeedCollection $this */
        // $this
        //     ->setActor($user)
        //     ->setFilter('global')
        //     ->setAlgorithm('top')
        //     ->setType('activity')
        //     ->setPeriod('12h')
        //     ->fetch()
        //     ->shouldBeAResponse([]);
    }

    public function it_should_throw_if_no_filter_during_fetch()
    {
        // /** @var FeedCollection $this */
        // $this
        //     ->setFilter('')
        //     ->setAlgorithm('top')
        //     ->setType('activity')
        //     ->setPeriod('12h')
        //     ->shouldThrow(new Exception('Missing filter'))
        //     ->duringFetch();
    }

    public function it_should_throw_if_no_algorithm_during_fetch()
    {
        // /** @var FeedCollection $this */
        // $this
        //     ->setFilter('global')
        //     ->setAlgorithm('')
        //     ->setType('activity')
        //     ->setPeriod('12h')
        //     ->shouldThrow(new Exception('Missing algorithm'))
        //     ->duringFetch();
    }

    public function it_should_throw_if_no_type_during_fetch()
    {
        // /** @var FeedCollection $this */
        // $this
        //     ->setFilter('global')
        //     ->setAlgorithm('top')
        //     ->setType('')
        //     ->setPeriod('12h')
        //     ->shouldThrow(new Exception('Missing type'))
        //     ->duringFetch();
    }

    public function it_should_throw_if_no_period_during_fetch()
    {
        // /** @var FeedCollection $this */
        // $this
        //     ->setFilter('global')
        //     ->setAlgorithm('top')
        //     ->setType('activity')
        //     ->setPeriod('')
        //     ->shouldThrow(new Exception('Missing period'))
        //     ->duringFetch();
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
