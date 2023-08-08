<?php
namespace Minds\Core\ActivityPub\Types\Object;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\Core\ObjectType;
use Minds\Entities\Activity;

class NoteType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Note';

    public function withActivity(Activity $activity): NoteType
    {
        $instance = clone $this;

        $instance->content = $activity->getMessage();

        // is t

        // Is this public?
        $instance->to = [
            'https://www.w3.org/ns/activitystreams#Public'
        ];

        // CC in followers
        $instance->cc = [];

        return $instance;
    }
}
