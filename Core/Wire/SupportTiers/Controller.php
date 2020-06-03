<?php
namespace Minds\Core\Wire\SupportTiers;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Exceptions\UserErrorException;
use Minds\Helpers\Urn;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Wire Support Tiers HTTP Controller
 * @package Minds\Core\Wire\SupportTiers
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /**
     * Controller constructor.
     * @param $manager
     * @param $entitiesBuilder
     */
    public function __construct(
        $manager = null,
        $entitiesBuilder = null
    ) {
        $this->manager = $manager ?: new Manager();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * Gets a single support tier
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getSingle(ServerRequest $request): JsonResponse
    {
        $urn = Urn::parse($request->getAttribute('parameters')['urn'], 'support-tier');

        if (!$urn || count($urn) !== 2) {
            throw new UserErrorException('Invalid URN', 400);
        }

        $supportTier = new SupportTier();
        $supportTier
            ->setEntityGuid($urn[0])
            ->setGuid($urn[1]);

        return new JsonResponse([
            'status' => 'success',
            'support_tier' => $this->manager->get($supportTier)
        ]);
    }

    /**
     * Gets the list of Support Tiers for an entity
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getAll(ServerRequest $request): JsonResponse
    {
        $guid = $request->getAttribute('parameters')['guid'];

        $entity = $guid ?
            $this->entitiesBuilder->single($guid) :
            $request->getAttribute('_user');

        if (!$entity) {
            throw new UserErrorException('No entity', 400);
        }

        $this->manager->setEntity($entity);

        return new JsonResponse([
            'status' => 'success',
            'support_tiers' => $this->manager->getAll()
        ]);
    }

    /**
     * Creates a new Support Tier
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function create(ServerRequest $request): JsonResponse
    {
        $currentUser = $request->getAttribute('_user');
        $body = $request->getParsedBody();
        $name = $body['name'] ?? '';
        $description = $body['description'] ?? '';
        $usd = round((float) ($body['usd'] ?? 0), 6);
        $tokens = round((float) ($body['tokens'] ?? 0), 6);

        if ($usd < 0) {
            throw new UserErrorException('Invalid USD amount', 400);
        } elseif ($tokens < 0) {
            throw new UserErrorException('Invalid tokens amount', 400);
        } elseif (!is_string($name) || strlen($name) === 0) {
            throw new UserErrorException('Invalid name', 400);
        }

        $supportTier = new SupportTier();
        $supportTier
            ->setEntityGuid($currentUser->guid)
            ->setPublic(true)
            ->setName($name)
            ->setDescription($description)
            ->setUsd($usd)
            ->setTokens($tokens);

        $this->manager
            ->setEntity($currentUser);

        return new JsonResponse([
            'status' => 'success',
            'support_tier' => $this->manager->create($supportTier),
        ]);
    }

    /**
     * Updates a Support Tier
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws UserErrorException
     * @throws Exception
     */
    public function update(ServerRequest $request): JsonResponse
    {
        $currentUser = $request->getAttribute('_user');
        $urn = Urn::parse($request->getAttribute('parameters')['urn'], 'support-tier');

        if (!$urn || count($urn) !== 2) {
            throw new UserErrorException('Invalid URN', 400);
        } elseif ($urn[0] !== (string) $currentUser->guid) {
            throw new UserErrorException('You are not authorized', 403);
        }

        $body = $request->getParsedBody();
        $name = $body['name'] ?? '';
        $description = $body['description'] ?? '';

        if (!is_string($name) || strlen($name) === 0) {
            throw new UserErrorException('Invalid name', 400);
        }

        $supportTier = $this->manager->get(
            (new SupportTier())
                ->setEntityGuid($urn[0])
                ->setGuid($urn[1])
        );

        if (!$supportTier) {
            throw new UserErrorException('Unknown Support Tier');
        }

        $supportTier
            ->setName($name)
            ->setDescription($description);

        $this->manager
            ->setEntity($currentUser);

        return new JsonResponse([
            'status' => 'success',
            'support_tier' => $this->manager->update($supportTier),
        ]);
    }

    /**
     * Deletes a Support Tier
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function delete(ServerRequest $request): JsonResponse
    {
        $currentUser = $request->getAttribute('_user');
        $urn = Urn::parse($request->getAttribute('parameters')['urn'], 'support-tier');

        if (!$urn || count($urn) !== 2) {
            throw new UserErrorException('Invalid URN', 400);
        } elseif ($urn[0] !== (string) $currentUser->guid) {
            throw new UserErrorException('You are not authorized', 403);
        }

        $supportTier = new SupportTier();
        $supportTier
            ->setEntityGuid($urn[0])
            ->setGuid($urn[1]);

        $this->manager
            ->setEntity($currentUser)
            ->delete($supportTier);

        return new JsonResponse([
            'status' => 'success',
        ]);
    }
}
