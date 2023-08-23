<?php

declare(strict_types=1);

namespace Minds\Core\ActivityPub;

use GuzzleHttp\Exception\ClientException;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\LikeFactory;
use Minds\Core\ActivityPub\JsonActivityResponse;
use Minds\Core\ActivityPub\Factories\OutboxFactory;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Services\HttpSignatureService;
use Minds\Core\ActivityPub\Services\ProcessCollectionService;
use Minds\Core\ActivityPub\Types\Core\OrderedCollectionPageType;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * The controller for the ActivityPub module's endpoints
 */
class Controller
{
    public function __construct(
        private Manager $manager,
        private ActorFactory $actorFactory,
        private OutboxFactory $outboxFactory,
        private readonly LikeFactory $likeFactory,
        private EntitiesBuilder $entitiesBuilder,
        private Config $config,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonActivityResponse
     * @throws InvalidArgumentException
     */
    public function getUser(ServerRequestInterface $request): JsonActivityResponse
    {
        $user = $this->buildUser($request);

        $person = $this->actorFactory->fromEntity($user);

        return new JsonActivityResponse([
            ...$person->getContextExport(),
            ...$person->export()
        ]);
    }

    public function postInbox(ServerRequestInterface $request): JsonActivityResponse
    {
        $this->verifySignature($request);
        
        $payload = $request->getParsedBody();

        $actor = $this->actorFactory->fromUri(JsonLdHelper::getValueOrId($payload['actor']));

        /** @var ProcessCollectionService */
        $proccessCollectionService = Di::_()->get(ProcessCollectionService::class);
        $proccessCollectionService
            ->withJson($payload)
            ->withActor($actor)
            ->process();

        return new JsonActivityResponse([]);
    }

    public function getUserOutbox(ServerRequestInterface $request): JsonActivityResponse
    {
        $user = $this->buildUser($request);

        $orderedCollection = $this->outboxFactory->build((string) $request->getUri(), $user);

        return new JsonActivityResponse([
            ...$orderedCollection->getContextExport(),
            ...$orderedCollection->export()
        ]);
    }

    public function getUserFollowers(ServerRequestInterface $request): JsonActivityResponse
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
            $person = $this->actorFactory->fromEntity($user);
            $items[] = $person->getId();
        }
    
        $orderedCollection = new OrderedCollectionPageType();
        $orderedCollection->id = ((string) $request->getUri());

        $baseUrl = $this->buildBaseUrl($user);
        $orderedCollection->setPartOf($baseUrl . 'followers');

        $orderedCollection->setOrderedItems($items);

        return new JsonActivityResponse([
            ...$orderedCollection->getContextExport(),
            ...$orderedCollection->export()
        ]);
    }

    public function getUserFollowing(ServerRequestInterface $request): JsonActivityResponse
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
            $person = $this->actorFactory->fromEntity($user);
            $items[] = $person->getId();
        }
        
        $orderedCollection = new OrderedCollectionPageType();
        $orderedCollection->id = ((string) $request->getUri());

        $baseUrl = $this->buildBaseUrl($user);
        $orderedCollection->setPartOf($baseUrl . 'following');

        $orderedCollection->setOrderedItems($items);

        return new JsonActivityResponse([
            ...$orderedCollection->getContextExport(),
            ...$orderedCollection->export()
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws NotFoundException
     * @throws UserErrorException
     * @throws \NotImplementedException
     */
    public function getUserLiked(ServerRequestInterface $request): JsonActivityResponse
    {
        $user = $this->buildUser($request);

        $orderedCollection = $this->likeFactory->build((string) $request->getUri(), $user);

        return new JsonActivityResponse([
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

        try {
            $actor = $this->actorFactory->fromUri($keyId);
        } catch (ClientException $e) {
            throw new ForbiddenException();
        }

        $context = new \HttpSignatures\Context([
            'keys' => [$keyId => $actor->publicKey->publicKeyPem],
        ]);

        if (!$context->verifier()->isSigned($request)) {
            throw new ForbiddenException();
        }
    }

}
