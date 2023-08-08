<?php
namespace Minds\Core\ActivityPub\Types\Object;

use Minds\Core\ActivityPub\Types\Actor\PersonType;
use Minds\Core\ActivityPub\Types\Core\ObjectType;
use NotImplementedException;

class ObjectFactory
{
    public static function build(array $json): ObjectType
    {
        $object = match ($json['type']) {
            'Note' => new NoteType(),
            default => new NotImplementedException(),
        };

        $object->id = $json['id'];

        switch (get_class($object)) {
            case NoteType::class:
                $object->content = $json['content'];
                break;
        }

        return $object;
    }
}