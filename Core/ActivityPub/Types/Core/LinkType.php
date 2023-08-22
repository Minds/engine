<?php
namespace Minds\Core\ActivityPub\Types\Core;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\AbstractType;

class LinkType extends AbstractType
{
    #[ExportProperty]
    protected string $type = 'Link';

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-href
     */
    #[ExportProperty]
    public string $href;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-hreflang
     */
    #[ExportProperty]
    public string $hreflang;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-rel
     */
    #[ExportProperty]
    public string $rel;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-mediaType
     */
    #[ExportProperty]
    public string $mediaType;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name
     */
    #[ExportProperty]
    public string $name;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-height
     */
    #[ExportProperty]
    public int $height;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-width
     */
    #[ExportProperty]
    public int $width;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-preview
     */
    #[ExportProperty]
    public LinkType|ObjectType $preview;
}
