<?php
namespace Minds\Core\ActivityPub\Types\Core;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\AbstractType;

class SourceType extends AbstractType
{
    protected string $type = 'Source';

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-content
     */
    #[ExportProperty]
    public string $content;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-mediaType
     */
    #[ExportProperty]
    public string $mediaType;
}
