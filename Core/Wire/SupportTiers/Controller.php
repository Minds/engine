<?php
namespace Minds\Core\Wire\SupportTiers;

use Exception;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Entities\User;
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
    /** @var Config */
    protected $config;

    /** @var FeaturesManager */
    protected $features;

    /** @var Manager */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Delegates\UserWireRewardsMigrationDelegate */
    protected $userWireRewardsMigration;

    /** @var Delegates\CurrenciesDelegate */
    protected $currenciesDelegate;

    /**
     * Controller constructor.
     * @param $config
     * @param $features
     * @param $manager
     * @param $entitiesBuilder
     * @param $userWireRewardsMigration
     * @param $currenciesDelegate
     */
    public function __construct(
        $config = null,
        $features = null,
        $manager = null,
        $entitiesBuilder = null,
        $userWireRewardsMigration = null,
        $currenciesDelegate = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->features = $features ?: Di::_()->get('Features\Manager');
        $this->manager = $manager ?: new Manager();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->userWireRewardsMigration = $userWireRewardsMigration ?: new Delegates\UserWireRewardsMigrationDelegate();
        $this->currenciesDelegate = $currenciesDelegate ?: new Delegates\CurrenciesDelegate();
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

        if (!$this->features->has('support-tiers')) {
            $supportTiers = [];
        } else {
            $supportTiers = $this->manager
                ->setEntity($entity)
                ->getAll();
        }

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
}
