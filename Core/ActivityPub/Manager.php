<?php
namespace Minds\Core\ActivityPub;

use GuzzleHttp\Exception\ConnectException;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\Comments\Comment;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
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

    /**
     * Saves an Actor to the datastore, for easy access later
     */
    public function addActor(AbstractActorType $actor, User $user): bool
    {
        // Add the URI to the database
        $this->addUri($actor->id, (int) $user->getGuid(), $user->getUrn());

        return $this->repository->addActor($actor);
    }

    /**
     * Retains a reference of URI to minds GUIDS
     */
    public function addUri(string $uri, int $guid, string $urn): bool
    {
        return $this->repository->addUri(
            uri: $uri,
            domain: JsonLdHelper::getDomainFromUri($uri),
            entityUrn: $urn,
            entityGuid: $guid,
        );
    }

    /**
     * Returns a URI (if available) from a Minds guid
     * First builds the entity from a urn, then calls the ->getUriFromEntity() function
     */
    public function getUriFromUrn(string $urn): ?string
    {
        /**
         * Try to find the entity by its urn
         */
        $entity = $this->entitiesBuilder->getByUrn($urn);

        /**
         * Return the uri of the entity
         */
        return $this->getUriFromEntity($entity);
    }

    /**
     * Returns a Uri for an entity
     */
    public function getUriFromEntity(EntityInterface $entity): string
    {
        /**
         * Attempt to get the uri from the remote (mysql database)
         */
        $uri = $this->repository->getUriFromUrn($entity->getUrn());

        if ($uri) {
            return $uri;
        }

        /**
         * If not found in the table we construct the uri manually
         */
        $url = $this->getBaseUrl() . 'users/' . $entity->getOwnerGuid();

        if ($entity instanceof User) {
            return $url;
        }

        return $url . '/entities/' . $entity->getUrn();
    }

    /**
     * Returns a Minds entity from a uri
     * Supports returning entities from both remote and local uris
     */
    public function getEntityFromUri(string $uri): ?EntityInterface
    {
        // Does the $objectUri start with our local domain
        if ($this->isLocalUri($uri)) {
            return $this->getEntityFromLocalUri($uri);
        }

        // Do we have a copy of this locally?
        if ($localUrn = $this->repository->getUrnFromUri($uri)) {
            $localEntity = $this->entitiesBuilder->getByUrn($localUrn);

            if (!$localEntity) {
                //throw new NotFoundException("The local entity could not be found. It may have been deleted");
                return false;
            }

            return $localEntity;
        }

        return null;
    }

    /**
     * Returns a Minds entity from its ActivityPub id. This function should be used
     * when the $objectUri is a local one.
     */
    private function getEntityFromLocalUri(string $objectUri): ?EntityInterface
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
            $entityUrn = $pathParts[3];

            $entity = $this->entitiesBuilder->getByUrn($entityUrn);

            if (!($entity instanceof Activity || $entity instanceof Comment)) {
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
     * Returns a list of inboxes to target for a users followers.
     * If there is no sharedInbox, it will coalesce to an inbox url
     */
    public function getInboxesForFollowers(int $userGuid): iterable
    {
        return $this->repository->getInboxesForFollowers($userGuid);
    }

    /**
     * Returns the base url that we will use for all of our Ids
     */
    public function getBaseUrl(): string
    {
        return $this->config->get('site_url') . 'api/activitypub/';
    }

    /**
     * Returns a transient id
     */
    public function getTransientId(): string
    {
        return $this->getBaseUrl() . 'transient/' . Guid::build();
    }
    
    /**
     * Returns true if the activity pub uri matches the Minds site url
     */
    public function isLocalUri($uri): bool
    {
        return strpos($uri, $this->getBaseUrl(), 0) === 0;
    }

    /**
     * Helper function to return an entity from a guid
     * (we do this to hack comments...)
     */
    private function getEntityFromGuid(int $guid): ?EntityInterface
    {
        $entity = $this->entitiesBuilder->single($guid);

        if (!$entity) {
            // Could this be a comment? Improve this as it is not efficient
            $entity = $this->entitiesBuilder->single('urn:comment:' . $guid);

            if (!$entity) {
                return null;
            }
        }

        return $entity;
    }
}
