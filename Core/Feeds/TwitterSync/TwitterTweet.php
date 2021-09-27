<?php
namespace Minds\Core\Feeds\TwitterSync;

use Minds\Traits\MagicAttributes;

/**
 * @method self setTwitterUser(TwktterUser $twitterUser)
 * @method TwitterUser getTwitterUser()
 * @method self setText(string $text)
 * @method string getText()
 * @method self setUrls(array $urls)
 * @method string[] getUrls()
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
}
