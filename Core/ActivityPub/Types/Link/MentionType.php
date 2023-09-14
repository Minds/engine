<?php
namespace Minds\Core\ActivityPub\Types\Link;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\Core\LinkType;

class MentionType extends LinkType
{
    #[ExportProperty]
    protected string $type = 'Mention';
}
