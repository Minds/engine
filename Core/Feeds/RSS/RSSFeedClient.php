<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS;

use Laminas\Feed\Reader\Reader;

class RSSFeedClient
{
    public function __construct(
        private readonly Reader $reader
    ) {
    }

    /**
     * @param string $url
     * @return iterable<array<string, string>>
     */
    public function fetchFeed(string $url): iterable
    {
        $feed = $this->reader->import($url); // TODO: add offset details

        foreach ($feed as $entry) {
            yield [
                'title' => $entry->getTitle(),
                'description'  => $entry->getDescription(),
                'dateModified' => $entry->getDateModified(),
                'authors'      => $entry->getAuthors(),
                'link'         => $entry->getLink(),
                'content'      => $entry->getContent(),
            ];
        }
    }
}
