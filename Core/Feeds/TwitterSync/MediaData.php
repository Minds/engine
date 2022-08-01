<?php
namespace Minds\Core\Feeds\TwitterSync;

use Minds\Traits\MagicAttributes;

/**
 * Tweet media data object.
 * @method self setType(string $type)
 * @method string getType()
 * @method self setUrl(string $url)
 * @method string getUrl()
 * @method self setHeight(int $height)
 * @method int getHeight()
 * @method self setWidth(int $width)
 * @method int getWidth()
 */
class MediaData
{
    use MagicAttributes;

    /** @var string - media type */
    protected $type;

    /** @var string - url of media */
    protected $url;

    /** @var string - height of media*/
    protected $height;

    /** @var string - width of media */
    protected $width;
}
