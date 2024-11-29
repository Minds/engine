<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Feeds\RSS\ActivityPatchers;

use Laminas\Feed\Reader\Entry\AbstractEntry;
use Laminas\Feed\Reader\Entry\EntryInterface;
use Minds\Core\Feeds\RSS\ActivityPatchers\AudioActivityPatcher;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Audio\AudioEntity;
use Minds\Core\Media\Audio\AudioService;
use Minds\Core\Media\MediaDownloader\MediaDownloaderInterface;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Laminas\Feed\Reader\Extension\Podcast\Entry as PodcastEntry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AudioActivityPatcherSpec extends ObjectBehavior
{
    private Collaborator $audioServiceMock;
    private Collaborator $imageDownloaderMock;
    private Collaborator $loggerMock;

    public function let(
        AudioService $audioServiceMock,
        MediaDownloaderInterface $imageDownloaderMock,
        Logger $loggerMock
    ) {
        $this->audioServiceMock = $audioServiceMock;
        $this->imageDownloaderMock = $imageDownloaderMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith($audioServiceMock, $imageDownloaderMock, $loggerMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AudioActivityPatcher::class);
    }

    public function it_should_patch_an_activity_with_podcast_data(
        Activity $activity,
        MockEntry $entry,
        PodcastEntry $podcastEntry,
        User $owner,
        \stdClass $enclosure,
        ResponseInterface $response,
        StreamInterface $stream
    ) {
        $enclosureUrl = 'https://example.minds.com/audio.mp3';

        $podcastImage = 'https://example.minds.com/image.jpg';
        $podcastSummary = 'Podcast summary';
        $podcastTitle = 'Podcast title';

        $entryTitle = 'Entry title';
        $entryDescription = 'Entry description';

        $richEmbedThumbnail = 'https://example.minds.com/thumbnail.jpg';
        $richEmbedTitle = 'Rich embed title';
        $richEmbedDescription = 'Rich embed description';

        $richEmbedData = [
            'meta' => [
                'title' => $richEmbedTitle,
                'description' => $richEmbedDescription
            ],
            'links' => [
                'thumbnail' => [
                    ['href' => $richEmbedThumbnail]
                ]
            ]
        ];

        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $entry->getExtensions()->willReturn(['Podcast\Entry' => $podcastEntry]);

        $podcastEntry->getItunesImage()->willReturn($podcastImage);
        $podcastEntry->getSummary()->willReturn($podcastSummary);
        $podcastEntry->getTitle()->willReturn($podcastTitle);

        $entry->getTitle()->willReturn($entryTitle);
        $entry->getDescription()->willReturn($entryDescription);
        
        $enclosure->url = $enclosureUrl;
        $entry->getEnclosure()->willReturn($enclosure);

        $this->audioServiceMock->onRemoteFileUrlProvided(
            owner: $owner,
            url: $enclosureUrl
        )->willReturn($audioEntity);

        $response->getHeader('Content-Type')->willReturn(['image/jpeg']);
        $stream->getContents()->willReturn('image data');
        $response->getBody()->willReturn($stream);

        $this->imageDownloaderMock->download(Argument::any())
            ->willReturn($response);

        $this->audioServiceMock->onRemoteFileUrlProvided(
            owner: $owner,
            url: $enclosureUrl
        )->willReturn($audioEntity);

        $this->audioServiceMock->uploadThumbnailFromBlob(
            $audioEntity,
            Argument::type('string')
        )->shouldBeCalled();

        $activity->setEntityGuid(Argument::any())->willReturn($activity);
        $activity->setAttachments([$audioEntity])->willReturn($activity);
        $activity->setTitle('Podcast title')->willReturn($activity);
        $activity->setMessage('Podcast summary')->willReturn($activity);

        $this->patch($activity, $entry, $owner, $richEmbedData)->shouldReturn($activity);
    }

    public function it_should_patch_an_activity_with_no_podcast_data(
        Activity $activity,
        MockEntry $entry,
        PodcastEntry $podcastEntry,
        User $owner,
        \stdClass $enclosure,
        ResponseInterface $response,
        StreamInterface $stream
    ) {
        $enclosureUrl = 'https://example.minds.com/audio.mp3';

        $podcastImage = null;
        $podcastSummary = null;
        $podcastTitle = null;

        $entryTitle = 'Entry title';
        $entryDescription = 'Entry description';

        $richEmbedThumbnail = 'https://example.minds.com/thumbnail.jpg';
        $richEmbedTitle = 'Rich embed title';
        $richEmbedDescription = 'Rich embed description';

        $richEmbedData = [
            'meta' => [
                'title' => $richEmbedTitle,
                'description' => $richEmbedDescription
            ],
            'links' => [
                'thumbnail' => [
                    ['href' => $richEmbedThumbnail]
                ]
            ]
        ];

        $audioEntity = new AudioEntity(
            guid: 123,
            ownerGuid: 456
        );

        $entry->getExtensions()->willReturn(['Podcast\Entry' => $podcastEntry]);

        $podcastEntry->getItunesImage()->willReturn($podcastImage);
        $podcastEntry->getSummary()->willReturn($podcastSummary);
        $podcastEntry->getTitle()->willReturn($podcastTitle);

        $entry->getTitle()->willReturn($entryTitle);
        $entry->getDescription()->willReturn($entryDescription);
        
        $enclosure->url = $enclosureUrl;
        $entry->getEnclosure()->willReturn($enclosure);

        $this->audioServiceMock->onRemoteFileUrlProvided(
            owner: $owner,
            url: $enclosureUrl
        )->willReturn($audioEntity);

        $response->getHeader('Content-Type')->willReturn(['image/jpeg']);
        $stream->getContents()->willReturn('image data');
        $response->getBody()->willReturn($stream);

        $this->imageDownloaderMock->download(Argument::any())
            ->willReturn($response);

        $this->audioServiceMock->onRemoteFileUrlProvided(
            owner: $owner,
            url: $enclosureUrl
        )->willReturn($audioEntity);

        $this->audioServiceMock->uploadThumbnailFromBlob(
            $audioEntity,
            Argument::type('string')
        )->shouldBeCalled();

        $activity->setEntityGuid(Argument::any())->willReturn($activity);
        $activity->setAttachments([$audioEntity])->willReturn($activity);
        $activity->setTitle('Entry title')->willReturn($activity);
        $activity->setMessage('Entry description')->willReturn($activity);

        $this->patch($activity, $entry, $owner, $richEmbedData)->shouldReturn($activity);
    }
}

/**
 * Interface exposed by this class isn't constructed properly, so we need to mock it.
 */
class MockEntry extends AbstractEntry implements EntryInterface
{
    public function getAuthor($index = 0)
    {
        return null;
    }
    public function getAuthors()
    {
        return null;
    }
    public function getContent()
    {
        return null;
    }
    public function getDateCreated()
    {
        return null;
    }
    public function getDateModified()
    {
        return null;
    }
    public function getDescription()
    {
        return null;
    }
    public function getEnclosure()
    {
        return null;
    }
    public function getId()
    {
        return null;
    }
    public function getLink($index = 0)
    {
        return null;
    }
    public function getLinks()
    {
        return null;
    }
    public function getPermalink()
    {
        return null;
    }
    public function getTitle()
    {
        return null;
    }
    public function getCommentCount()
    {
        return null;
    }
    public function getCommentLink()
    {
        return null;
    }
    public function getCommentFeedLink()
    {
        return null;
    }
    public function getCategories()
    {
        return null;
    }
    public function getExtensions()
    {
        return [];
    }
}
