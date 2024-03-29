<?php
namespace Minds\Core\Wire\SupportTiers;

use Exception;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Minds\Helpers\Urn;
use Minds\Api\Exportable;
use Minds\Common\Repositoy\Response;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Wire Support Tiers HTTP Controller
 * @package Minds\Core\Wire\SupportTiers
 */
class Controller
{
    /** @var Config */
    protected $config;

    /** @var Manager */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Delegates\CurrenciesDelegate */
    protected $currenciesDelegate;

    /** @var Members */
    protected $members;

    /**
     * Controller constructor.
     * @param $config
     * @param $manager
     * @param $entitiesBuilder
     * @param $currenciesDelegate
     * @param $members
     */
    public function __construct(
        $config = null,
        $manager = null,
        $entitiesBuilder = null,
        $currenciesDelegate = null,
        $members = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->manager = $manager ?: new Manager();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->currenciesDelegate = $currenciesDelegate ?: new Delegates\CurrenciesDelegate();
        $this->members = $members ?? new Members();
    }

    /**
     * Gets a single support tier
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getSingle(ServerRequest $request): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'support_tier' => $this->manager->getByUrn($request->getAttribute('parameters')['urn'])
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

        $supportTiers = $this->manager
            ->setEntity($entity)
            ->getAll();

        return new JsonResponse([
            'status' => 'success',
            'support_tiers' => $supportTiers
        ]);
    }

    /**
     * Creates a new public Support Tier
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function createPublic(ServerRequest $request): JsonResponse
    {
        $currentUser = $request->getAttribute('_user');
        $body = $request->getParsedBody();
        $name = $body['name'] ?? '';
        $description = $body['description'] ?? '';
        $usd = round((float) ($body['usd'] ?? 0), 6);
        $hasUsd = (bool) ($body['has_usd'] ?? false);
        $hasTokens = (bool) ($body['has_tokens'] ?? false);

        if ($usd < 0) {
            throw new UserErrorException('Invalid USD amount', 400);
        } elseif (!$hasUsd && !$hasTokens) {
            throw new UserErrorException('You need to enable at least one currency', 400);
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
            ->setHasUsd($hasUsd)
            ->setHasTokens($hasTokens);

        $this->manager
            ->setEntity($currentUser);

        return new JsonResponse([
            'status' => 'success',
            'support_tier' => $this->manager->create($supportTier),
        ]);
    }

    /**
     * Creates a new private (custom) Support Tier
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws UserErrorException
     */
    public function createPrivate(ServerRequest $request): JsonResponse
    {
        $currentUser = $request->getAttribute('_user');
        $body = $request->getParsedBody();
        $usd = round((float) ($body['usd'] ?? 0), 6);
        $hasUsd = (bool) ($body['has_usd'] ?? false);
        $hasTokens = (bool) ($body['has_tokens'] ?? false);

        if ($usd < 0) {
            throw new UserErrorException('Invalid USD amount', 400);
        } elseif (!$hasUsd && !$hasTokens) {
            throw new UserErrorException('You need to enable at least one currency', 400);
        }

        $name = ['Custom', $usd];

        if ($hasUsd) {
            $name[] = 'USD';
        }

        if ($hasTokens) {
            $name[] = 'Tokens';
        }

        $supportTier = new SupportTier();
        $supportTier
            ->setEntityGuid($currentUser->guid)
            ->setPublic(false)
            ->setName(implode(' ', $name))
            ->setDescription('')
            ->setUsd($usd)
            ->setHasUsd($hasUsd)
            ->setHasTokens($hasTokens);

        $this->manager
            ->setEntity($currentUser);

        $result = $this->manager->match($supportTier);

        if (!$result) {
            $result = $this->manager->create($supportTier);
        }

        return new JsonResponse([
            'status' => 'success',
            'support_tier' => $result,
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

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getMembers(ServerRequest $request): JsonResponse
    {
        /** @var string */
        $entityGuid = $request->getAttribute('parameters')['entityGuid'];

        /** @var string */
        $supportTierUrn = $request->getAttribute('parameters')['supportTierUrn'] ?? null;

        /** @var Members */
        $members = $this->members->setEntityGuid($entityGuid);

        /** @var SupportTier */
        $supportTier = $supportTierUrn ? $this->manager->getByUrn($supportTierUrn) : null;

        if ($supportTier) {
            $members = $members->setSupportTier($supportTier);
        }

        /** @var Response */
        $list = $members->getList();

        return new JsonResponse([
            'status' => 'success',
            'members' => Exportable::_($list),
        ]);
    }
}
