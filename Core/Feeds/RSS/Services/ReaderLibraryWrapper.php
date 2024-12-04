<?php
namespace Minds\Core\Feeds\RSS\Services;

use Laminas\Feed\Reader\Feed\FeedInterface;
use Laminas\Feed\Reader\Reader;

class ReaderLibraryWrapper
{
    public function __construct(private Reader $reader)
    {
        
    }

    /**
     * Import a feed by providing a url
     */
    public function import(string $url): FeedInterface
    {
        $this->reader->registerExtension('Podcast');
        return $this->reader->import($url);
    }
}
