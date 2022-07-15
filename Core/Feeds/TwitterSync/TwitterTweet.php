<?php
namespace Minds\Core\Feeds\TwitterSync;

use Minds\Traits\MagicAttributes;

/**
 * @method self setId(string $id)
 * @method string getId()
 * @method self setTwitterUser(TwitterUser $twitterUser)
 * @method TwitterUser getTwitterUser()
 * @method self setText(string $text)
 * @method string getText()
 * @method self setUrls(array $urls)
 * @method string[] getUrls()
 * @method self setMediaData(MediaData[] $mediaData)
 * @method MediaData[] getMediaData()
 */
class TwitterTweet
{
    use MagicAttributes;

    /** @var string */
    protected $id;

    /** @var TwitterUser */
    protected $twitterUser;

    /** @var string */
    protected $text;

    /** @var string[] */
    protected $urls = [];

    /** @var MediaData[] - array of MediaData */
    protected $mediaData = [];

    /**
     * Get media data filtered by photos.
     * @return array - MediaData[] array of MediaData for photos.
     */
    public function getPhotosData(): array
    {
        return array_filter($this->getMediaData(), function ($media) {
            return $media->getType() === 'photo';
        }) ?? [];
    }
}
