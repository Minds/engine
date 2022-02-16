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
 * @method self setImageUrls(array $urls)
 * @method string[] getImageUrls()
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

    /** @var string[] */
    protected $imageUrls = [];
}
