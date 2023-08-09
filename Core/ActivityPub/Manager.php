<?php
namespace Minds\Core\ActivityPub;

use GuzzleHttp\Exception\ConnectException;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Webfinger;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;

class Manager
{
    public function __construct(
        protected Repository $repository,
        protected EntitiesBuilder $entitiesBuilder,
        protected Config $config,
        protected Client $client,
    ) {
        
    }


    public function addActor(AbstractActorType $actor, int $guid): bool
    {
        // Add the URI to the database
        $this->addUri($actor->id, $guid);

        return $this->repository->addActor($actor);
    }

    public function addUri(string $uri, int $guid): bool
    {
        return $this->repository->addUri(
            uri: $uri,
            domain: JsonLdHelper::getDomainFromUri($uri),
            guid: $guid,
        );
    }

    public function getEntityFromUri(string $uri): ?EntityInterface
    {
        // Does the $objectUri start with our local domain
        if ($this->isLocalUri($uri)) {
            return $this->getEntityFromLocalUri($uri);
        }

        // Do we have a copy of this locally?
        if ($localGuid = $this->repository->getGuidFromUri($uri)) {
            $localEntity = $this->entitiesBuilder->single($localGuid);

            if (!$localEntity) {
                //throw new NotFoundException("The local entity could not be found. It may have been deleted");
                return false;
            }

            return $localEntity;
        }

        return null;

        // // Fetch the remote object
        // // Does the uri start with http?
        // if (!strpos($objectUri, 'http', 0) === 0) {
        //     throw new UserErrorException("Minds only support IDs that are resolvable http(s) urls");
        // }

        // $payload = $this->client->request('GET', $objectUri);

        // if (!$payload) {
        //     throw new NotFoundException();
        // }

        // throw new NotImplementedException();

        // switch ($payload['type']) {

        // }
    }

    /**
     * Returns a Minds entity from its ActivityPub id. This function should be used
     * when the $objectUri is a local one.
     */
    public function getEntityFromLocalUri(string $objectUri): ?EntityInterface
    {
        if (!$this->isLocalUri($objectUri)) {
            throw new ServerErrorException("Non-local uri passed through to the getEntityFromLocalUri function");
        }

        $pathUri = str_replace($this->getBaseUrl(), '', $objectUri);

        $pathParts = explode('/', $pathUri);

        if (count($pathParts) === 2) {
            // This will be users/GUID
            $entityGuid = $pathParts[1];

            $user = $this->entitiesBuilder->single($entityGuid);

            if (!$user instanceof User) {
                //throw new NotFoundException();
                return null;
            }

            return $user;
        } elseif (count($pathParts) > 2 && $pathParts[2] === 'entities') {
            // This will be an activity/entity
            $entityGuid = $pathParts[3];

            $entity = $this->entitiesBuilder->single($entityGuid);

            if (!$entity instanceof Activity) {
                //throw new NotFoundException();
                return null;
            }

            return $entity;
        }

        return null;
    }

    /**
     * Returns a private key (RSA PKCS8) or creates and saves one
     */
    public function getPrivateKey(User $user): \phpseclib3\Crypt\RSA\PrivateKey
    {
        $userGuid = (int) $user->getGuid();
        $privateKey = $this->repository->getPrivateKey($userGuid);

        if ($privateKey) {
            return \phpseclib3\Crypt\PublicKeyLoader::loadPrivateKey($privateKey);
        } else {
            // No private key was found, so we will create one
            $private = \phpseclib3\Crypt\RSA::createKey();

            $success = $this->repository->addPrivateKey($userGuid, (string) $private);

            if (!$success) {
                throw new ServerErrorException("Unable to save private key for user");
            }
        
            return $private;
        }
    }

    /**
     * Returns true if the activity pub uri matches the Minds site url
     */
    private function isLocalUri($uri): bool
    {
        return strpos($uri, $this->getBaseUrl(), 0) === 0;
    }

    public function getBaseUrl(): string
    {
        return $this->config->get('site_url') . 'api/activitypub/';
    }
}
