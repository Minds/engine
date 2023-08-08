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

    public function withUser(User $user): PersonType
    {
        $person = clone $this;
        
        $baseUrl = Di::_()->get('Config')->get('site_url') . 'api/activitypub/';

        $person->name = $user->getName();
        $person->id = $baseUrl . 'users/' . $user->getGuid();

        $person->inbox = $person->id . '/inbox';
        $person->outbox = $person->id . '/outbox';
        $person->followers = $person->id . '/followers';
        $person->following = $person->id . '/following';
        $person->liked = $person->id . '/likes';

        // MAY have
        $person->preferredUsername = $user->getUsername();

        // Object
        $person->url = 'http://localhost:8080/' . $user->getUsername();
        $person->summary = $user->briefdescription;
        $person->icon = [
            'type' => 'Image',
            'url' => $user->getIconURL('large'),
        ];

        $private = Di::_()->get(\Minds\Core\ActivityPub\Manager::class)->getPrivateKey($user);
        $publicKey = $private->getPublicKey();
        // $keypair = $keypairManager->getKeypair($user);
        // $publicKey = "-----BEGIN PUBLIC KEY-----\n".base64_encode(pack("H*", hash('sha256',$keypairManager->getPublicKey($keypair))))."\n-----END PUBLIC KEY-----\n";
        

        $person->publicKey = new PublicKeyType(
            id: $person->id . '#main-key',
            owner: $person->id,
            publicKeyPem: $publicKey,
        );


        $person->endpoints = [
            'sharedInbox' => $baseUrl . 'inbox'
        ];

        return $person;
    }

}
