<?php
namespace Minds\Core\ActivityPub\Types\Actor;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\Core\ObjectType;
use Minds\Core\Di\Di;
use Minds\Entities\User;

/**
 * https://www.w3.org/TR/activitystreams-vocabulary/#dfn-person
 */
class PersonType extends AbstractActorType
{
    #[ExportProperty]
    protected string $type = 'Person';

    /**
    * @see https://www.w3.org/TR/activitypub/#actor-objects
    */
    #[ExportProperty]
    public string $following;

    /**
     * @see https://www.w3.org/TR/activitypub/#actor-objects
     */
    #[ExportProperty]
    public string $followers;

    /**
     * @see https://www.w3.org/TR/activitypub/#actor-objects
     */
    #[ExportProperty]
    public string $liked;

    /**
     * @see https://www.w3.org/TR/activitypub/#actor-objects
     */
    #[ExportProperty]
    public bool $manuallyApprovesFollowers = false;

}
