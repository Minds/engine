<?php

namespace Spec\Minds\Core\Feeds\RSS\Services;

use ArrayIterator;
use Laminas\Feed\Reader\Entry\EntryInterface;
use Laminas\Feed\Reader\Feed\Rss;
use Laminas\Feed\Reader\Reader;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Service as MetascraperService;
use Minds\Core\Feeds\RSS\Services\ProcessRssFeedService;
use Minds\Core\Feeds\Activity\Manager as ActivityManager;
use Minds\Core\Feeds\RSS\Repositories\RssImportsRepository;
use Minds\Core\Feeds\RSS\Services\ReaderLibraryWrapper;
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\Guid;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use stdClass;

class ProcessRssFeedServiceSpec extends ObjectBehavior
{
    private Collaborator $readerMock;
    private Collaborator $metascraperMock;
    private Collaborator $activityManagerMock;
    private Collaborator $rssImportsRepositoryMock;
    private Collaborator $aclMock;

    public function let(
        ReaderLibraryWrapper $readerMock,
        MetascraperService $metascraperMock,
        ActivityManager $activityManagerMock,
        RssImportsRepository $rssImportsRepositoryMock,
        ACL $aclMock
    ) {
        $this->beConstructedWith(
            $readerMock,
            $metascraperMock,
            $activityManagerMock,
            $rssImportsRepositoryMock,
            $aclMock,
            Di::_()->get('Logger'),
        );
        $this->readerMock = $readerMock;
        $this->metascraperMock = $metascraperMock;
        $this->activityManagerMock = $activityManagerMock;
        $this->rssImportsRepositoryMock = $rssImportsRepositoryMock;
        $this->aclMock = $aclMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ProcessRssFeedService::class);
    }

    public function it_should_return_rss_entries(Rss $rssIterator, EntryInterface $entry)
    {

        $rssIterator->valid()
            ->shouldBeCalled()
            ->willReturn(true, true, true, false);
        $rssIterator->next()
            ->shouldBeCalled();
        $rssIterator->key()
            ->willReturn(0, 1, 2);
        $rssIterator->current()
            ->willReturn($entry);
        $rssIterator->rewind()
            ->shouldBeCalled();


        $this->readerMock->import('https://php.spec/test.xml')
            ->shouldBeCalled()
            ->willReturn($rssIterator);

        $feed = new RssFeed(
            feedId: 1,
            userGuid: Guid::build(),
            title: 'Phpspec test',
            url: 'https://php.spec/test.xml'
        );

        $result = $this->fetchFeed($feed);
        $result->shouldHaveCount(3);
        $result[0]->shouldBeAnInstanceOf(EntryInterface::class);
    }

    public function it_should_return_feed_details(Rss $rssDetails)
    {
        $this->readerMock->import('https://php.spec/test.xml')
            ->shouldBeCalled()
            ->willReturn($rssDetails);
    

        $result = $this->getFeedDetails('https://php.spec/test.xml');
        $result->shouldBeAnInstanceOf(Rss::class);
    }

    public function it_should_process_activities_for_feed(EntryInterface $entry, User $owner)
    {
        $entry->getLink()
            ->shouldBeCalled()
            ->willReturn('https://php.spec/blog/post-1');

        $this->metascraperMock->scrape('https://php.spec/blog/post-1')
            ->shouldBeCalled()
            ->willReturn([
                'meta' => [
                    'title' => 'A blog post (1)',
                    'description' => 'I made a blog post for testing this function',
                    'canonical_url' => 'https://php.spec/blog/post-1',
                ],
                'links' => [
                    'thumbnail' => [
                        [
                            'href' => 'https://php.spec/assets/blog-1.png'
                        ]
                    ]
                ]
            ]);

        $this->rssImportsRepositoryMock->hasMatch(1, 'https://php.spec/blog/post-1')
            ->willReturn(false);

        $this->activityManagerMock->add(Argument::that(function (Activity $activity) {
            return $activity->getLinkTitle() === 'A blog post (1)'
                && $activity->getBlurb() === 'I made a blog post for testing this function'
                && $activity->perma_url === 'https://php.spec/blog/post-1';
        }))
            ->shouldBeCalled();

        $this->rssImportsRepositoryMock->addEntry(1, 'https://php.spec/blog/post-1', Argument::any())
            ->shouldBeCalled();

        $this->processActivity($entry, 1, $owner)
            ->shouldBe(true);
    }

    public function it_should_process_activities_for_feed_using_enclosure_url_if_not_link(EntryInterface $entry, User $owner)
    {
        $entry->getLink()
            ->shouldBeCalled()
            ->willReturn(null);
        
        $entry->getEnclosure()
            ->shouldBeCalled()
            ->willReturn((object) [ 'url' => 'https://php.spec/blog/post-2']);

        $this->metascraperMock->scrape('https://php.spec/blog/post-2')
            ->shouldBeCalled()
            ->willReturn([
                'meta' => [
                    'title' => 'A blog post (2)',
                    'description' => 'I made a blog post for testing this function',
                    'canonical_url' => 'https://php.spec/blog/post-2',
                ],
                'links' => [
                    'thumbnail' => [
                        [
                            'href' => 'https://php.spec/assets/blog-2.png'
                        ]
                    ]
                ]
            ]);

        $this->rssImportsRepositoryMock->hasMatch(1, 'https://php.spec/blog/post-2')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->activityManagerMock->add(Argument::that(function (Activity $activity) {
            return $activity->getLinkTitle() === 'A blog post (2)'
                && $activity->getBlurb() === 'I made a blog post for testing this function'
                && $activity->perma_url === 'https://php.spec/blog/post-2';
        }))
            ->shouldBeCalled();

        $this->rssImportsRepositoryMock->addEntry(1, 'https://php.spec/blog/post-2', Argument::any())
            ->shouldBeCalled();

        $this->processActivity($entry, 1, $owner)
            ->shouldBe(true);
    }

    
    public function it_should_process_activities_for_feed_using_enclosure_url_if_not_link_and_not_enclosure(EntryInterface $entry, User $owner)
    {
        $entry->getLink()
            ->shouldBeCalled()
            ->willReturn(null);
        
        $entry->getEnclosure()
            ->shouldBeCalled()
            ->willReturn(null);

        $entry->getPermalink()
            ->shouldBeCalled()
            ->willReturn('https://php.spec/blog/post-3');

        $this->metascraperMock->scrape('https://php.spec/blog/post-3')
            ->shouldBeCalled()
            ->willReturn([
                'meta' => [
                    'title' => 'A blog post (3)',
                    'description' => 'I made a blog post for testing this function',
                    'canonical_url' => 'https://php.spec/blog/post-3',
                ],
                'links' => [
                    'thumbnail' => [
                        [
                            'href' => 'https://php.spec/assets/blog-3.png'
                        ]
                    ]
                ]
            ]);

        $this->rssImportsRepositoryMock->hasMatch(1, 'https://php.spec/blog/post-3')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->activityManagerMock->add(Argument::that(function (Activity $activity) {
            return $activity->getLinkTitle() === 'A blog post (3)'
                && $activity->getBlurb() === 'I made a blog post for testing this function'
                && $activity->perma_url === 'https://php.spec/blog/post-3';
        }))
            ->shouldBeCalled();

        $this->rssImportsRepositoryMock->addEntry(1, 'https://php.spec/blog/post-3', Argument::any())
            ->shouldBeCalled();

        $this->processActivity($entry, 1, $owner)
            ->shouldBe(true);
    }

    public function it_should_skip_processing_if_no_link(EntryInterface $entry, User $owner)
    {
        $entry->getLink()
            ->shouldBeCalled()
            ->willReturn(null);
        
        $entry->getEnclosure()
            ->shouldBeCalled()
            ->willReturn(null);

        $entry->getPermalink()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->metascraperMock->scrape(Argument::any())
            ->shouldNotBeCalled();

        $this->activityManagerMock->add(Argument::any())
            ->shouldNotBeCalled();

        $this->processActivity($entry, 1, $owner)
            ->shouldBe(true);
    }

    public function it_should_skip_if_rss_item_has_already_been_imported_before_saving_activity(EntryInterface $entry, User $owner)
    {
        $entry->getLink()
            ->shouldBeCalled()
            ->willReturn('https://php.spec/blog/post-1');

        $this->metascraperMock->scrape('https://php.spec/blog/post-1')
            ->shouldBeCalled()
            ->willReturn([
                'meta' => [
                    'title' => 'A blog post (1)',
                    'description' => 'I made a blog post for testing this function',
                    'canonical_url' => 'https://php.spec/blog/post-1',
                ],
                'links' => [
                    'thumbnail' => [
                        [
                            'href' => 'https://php.spec/assets/blog-1.png'
                        ]
                    ]
                ]
            ]);

        $this->rssImportsRepositoryMock->hasMatch(1, 'https://php.spec/blog/post-1')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->activityManagerMock->add(Argument::any())
            ->shouldNotBeCalled();

        $this->processActivity($entry, 1, $owner)
            ->shouldBe(false);
    }
}
