<?php
namespace Minds\Core\ActivityPub\Types\Actor;

use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Core\ActivityPub\Types\Core\ObjectType;
use Minds\Core\Di\Di;
use Minds\Entities\User;

/**
 * https://www.w3.org/TR/activitypub/#actor-objects
 */
abstract class AbstractActorType extends ObjectType
{
    /**
     * @see https://www.w3.org/TR/activitypub/#actor-objects
     */
    #[ExportProperty]
    public string $inbox;

    /**
     * @see https://www.w3.org/TR/activitypub/#actor-objects
     */
    #[ExportProperty]
    public string $outbox;

    /**
     * @see https://www.w3.org/TR/activitypub/#actor-objects
     */
    #[ExportProperty]
    public string $preferredUsername;

    /**
     * @see https://www.w3.org/TR/activitypub/#actor-objects
     */
    #[ExportProperty]
    public array $endpoints;

    /**
     * @see https://www.w3.org/TR/activitypub/#actor-objects
     */
    #[ExportProperty]
    public bool $manuallyApprovesFollowers = false;

    #[ExportProperty]
    public PublicKeyType $publicKey;

    public function __construct()
    {
        $this->contexts[] = 'https://w3id.org/security/v1';
    }
}
