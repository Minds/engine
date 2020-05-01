<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Media\YouTubeImporter;

use Minds\Traits\MagicAttributes;

/**
 * Class Video
 * @package Minds\Core\Media\YouTubeImporter
 * @method string getYoutubeId()
 * @method Video setYoutubeId(string $value)
 * @method string getTitle()
 * @method Video setTitle(string $value)
 * @method string getPublishedAt()
 * @method Video setPublishedAt(string $value)
 * @method string getDescription()
 * @method Video setDescription(string $value)
 * @method string getChannelId()
 * @method Video setChannelId(string $value)
 * @method string getChannelTitle()
 * @method Video setChannelTitle(string $value)
 * @method string getMindsGuid()
 * @method Video setMindsGuid(string $value)
 * @method array getThumbnails()
 * @method Video setThumbnails(array $value)
 */
class Video
{
    use MagicAttributes;

    protected $youtubeId;

    protected $title;

    protected $publishedAt;

    protected $description;

    protected $channelId;

    protected $channelTitle;

    protected $mindsGuid;

    protected $thumbnails;

    /**
     * Export
     * @param array $extras
     * @return array
     */
    public function export(array $extras = []): array
    {
        return [
            'youtubeId' => $this->youtubeId,
            'title' => $this->title,
            'publishedAt' => $this->publishedAt,
            'description' => $this->description,
            'channelId' => $this->channelId,
            'channelTitle' => $this->channelTitle,
            'mindsGuid' => $this->mindsGuid,
            'thumbnails' => $this->thumbnails,
        ];
    }
}
