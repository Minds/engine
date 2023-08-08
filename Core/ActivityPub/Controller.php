<?php

declare(strict_types=1);

namespace Minds\Core\ActivityPub;

use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Core\ActivityPub\Types\Actor\PersonType;
use Minds\Core\ActivityPub\Types\Core\OrderedCollectionPageType;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Services\HttpSignatureService;
use Minds\Core\ActivityPub\Services\ProcessCollectionService;

/**
 * The controller for the ActivityPub module's endpoints
 */
class Controller
{
    public function __construct(
        private Manager $manager,
        private EntitiesBuilder $entitiesBuilder,
        private Config $config,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function getUser(ServerRequestInterface $request): JsonResponse
    {
        $user = $this->buildUser($request);

        $person = (new PersonType())->withUser($user);

        return new JsonResponse([
            ...$person->getContextExport(),
            ...$person->export()
        ]);
    }

    public function postInbox(ServerRequestInterface $request): JsonResponse
    {
        $this->verifySignature($request);
        
        $payload = $request->getParsedBody();

        $actor = $this->manager->uriToActor(JsonLdHelper::getValueOrId($payload['actor']));

        /** @var ProcessCollectionService */
        $proccessCollectionService = Di::_()->get(ProcessCollectionService::class);
        $proccessCollectionService
            ->withJson($payload)
            ->withActor($actor)
            ->process();

        return new JsonResponse([]);
    }

    public function getUserOutbox(ServerRequestInterface $request): JsonResponse
    {
        $user = $this->buildUser($request);

        $orderedCollection = new OrderedCollectionPageType();
        $orderedCollection->setId((string) $request->getUri());

        $baseUrl = $this->buildBaseUrl($user);
        $orderedCollection->setPartOf($baseUrl . 'outbox');

        // TODO move to a class
        $elasticManager = Di::_()->get(\Minds\Core\Feeds\Elastic\V2\Manager::class);

        $queryOpts = new QueryOpts(
            user: $user,
            onlyOwn: true,
        );

        $items = [];

        foreach ($elasticManager->getLatest($queryOpts) as $entity) {

            $note = (new NoteType())->withActivity($entity);
            $note->setId($baseUrl . 'entities/' . $entity->getGuid());

            $item = new CreateType();
            $item->id = ($baseUrl . 'entities/' . $entity->getGuid() . '/activity');
            $item->actor = ((new PersonType())->withUser($user));
            $item->object = ($note);
    
            $items[] = $item;
        }


        $orderedCollection->setOrderedItems($items);

        return new JsonResponse([
            ...$orderedCollection->getContextExport(),
            ...$orderedCollection->export()
        ]);
    }

    public function getUserFollowers(ServerRequestInterface $request): JsonResponse
    {
        $user = $this->buildUser($request);

        $subscriptionsManager = new \Minds\Core\Subscriptions\Manager();
        $users = $subscriptionsManager->getList([
            'limit' => 12,
            'guid' => $user->getGuid(),
            'type' => 'subscribers'
        ]);

        $items = [];

        foreach ($users as $user) {
            $person = (new PersonType)->withUser($user);
            $items[] = $person->getId();
        }
    
        $orderedCollection = new OrderedCollectionPageType();
        $orderedCollection->id = ((string) $request->getUri());

        $baseUrl = $this->buildBaseUrl($user);
        $orderedCollection->setPartOf($baseUrl . 'followers');

        $orderedCollection->setOrderedItems($items);

        return new JsonResponse([
            ...$orderedCollection->getContextExport(),
            ...$orderedCollection->export()
        ]);
    }

    public function getUserFollowing(ServerRequestInterface $request): JsonResponse
    {
        $user = $this->buildUser($request);

        $subscriptionsManager = new \Minds\Core\Subscriptions\Manager();
        $users = $subscriptionsManager->getList([
            'limit' => 12,
            'guid' => $user->getGuid(),
            'type' => 'subscribed'
        ]);

        $items = [];

        foreach ($users as $user) {
            $person = (new PersonType)->withUser($user);
            $items[] = $person->getId();
        }
        
        $orderedCollection = new OrderedCollectionPageType();
        $orderedCollection->id = ((string) $request->getUri());

        $baseUrl = $this->buildBaseUrl($user);
        $orderedCollection->setPartOf($baseUrl . 'following');

        $orderedCollection->setOrderedItems($items);

        return new JsonResponse([
            ...$orderedCollection->getContextExport(),
            ...$orderedCollection->export()
        ]);
    }

    /**
     * @throws UserErrorException
     * @throws NotFoundException
     */
    protected function buildUser(ServerRequestInterface $request): User
    {
        $guid = $request->getAttribute('parameters')['guid'] ?? null;
        
        if (!$guid) {
            throw new UserErrorException("Invalid guid");
        }

        // get the minds channel from the resource
        $user = $this->entitiesBuilder->single($guid);

        if (!$user instanceof User) {
            throw new NotFoundException("$guid not found");
        }

        return $user;
    }

    protected function buildBaseUrl(User $user = null): string
    {
        $baseUrl = $this->config->get('site_url') . 'api/activitypub/';

        if ($user) {
            $baseUrl .= 'users/' . $user->getGuid() . '/';
        }

        return $baseUrl;
    }

    /**
     * This function will throw an exception if the signature fails
     * @throws ForbiddenException
     */
    private function verifySignature(ServerRequestInterface $request): void
    {
        $service = new HttpSignatureService();
        $keyId = $service->getKeyId($request->getHeader('Signature')[0]);

        $actor = $this->manager->uriToActor($keyId);

        $context = new \HttpSignatures\Context([
            'keys' => [$keyId => $actor->publicKey->publicKeyPem],
        ]);

        if (!$context->verifier()->isSigned($request)) {
            throw new ForbiddenException();
        }
    }

}
