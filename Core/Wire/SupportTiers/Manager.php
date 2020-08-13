<?php
namespace Minds\Core\Wire\SupportTiers;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\GuidBuilder;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Minds\Helpers\Urn;

/**
 * Wire Support Tiers Manager
 * @package Minds\Core\Wire\SupportTiers
 */
class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var GuidBuilder */
    protected $guidBuilder;

    /** @var Delegates\CurrenciesDelegate */
    protected $currenciesDelegate;

    /** @var Delegates\PaymentsDelegate */
    protected $paymentsDelegate;

    /** @var mixed */
    protected $entity;

    /**
     * Manager constructor.
     * @param $repository
     * @param $guidBuilder
     * @param $currenciesDelegate
     * @param $paymentsDelegate
     */
    public function __construct(
        $repository = null,
        $guidBuilder = null,
        $currenciesDelegate = null,
        $paymentsDelegate = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->guidBuilder = $guidBuilder ?: new GuidBuilder();
        $this->currenciesDelegate = $currenciesDelegate ?: new Delegates\CurrenciesDelegate();
        $this->paymentsDelegate = $paymentsDelegate ?: new Delegates\PaymentsDelegate();
    }

    /**
     * @param mixed $entity
     * @return Manager
     */
    public function setEntity($entity): Manager
    {
        // TODO: Check entity type
        $this->entity = $entity;
        return $this;
    }

    /**
     * Fetches all public Support Tiers for an entity.
     * @return Response<SupportTier>
     * @throws Exception
     */
    public function getAll(): Response
    {
        if (!$this->entity || !$this->entity->guid) {
            throw new Exception('Missing entity');
        }

        $response = $this->repository->getList(
            (new RepositoryGetListOptions())
                ->setEntityGuid((string) $this->entity->guid)
                ->setLimit(5000)
        )->filter(function (SupportTier $supportTier) {
            return $supportTier->isPublic();
        })->sort(function (SupportTier $a, SupportTier $b) {
            return $a->getUsd() <=> $b->getUsd();
        });

        return $response->map(function (SupportTier $supportTier) {
            return $this->hydrate($supportTier);
        });
    }

    /**
     * Gets a single Support Tier based on partial data
     * @param SupportTier $supportTier
     * @return SupportTier|null
     * @throws Exception
     */
    public function get(SupportTier $supportTier): ?SupportTier
    {
        if (!$supportTier->getEntityGuid() || !$supportTier->getGuid()) {
            throw new Exception('Missing primary key');
        }

        $tier = $this->repository->getList(
            (new RepositoryGetListOptions())
                ->setEntityGuid($supportTier->getEntityGuid())
                ->setGuid($supportTier->getGuid())
                ->setLimit(1)
        )->first();

        if (!$tier) {
            return null;
        }

        return $this->hydrate(
            $tier
        );
    }

    /**
     * Get by a urn
     * @param string $urn
     * @return SupportTier|null
     */
    public function getByUrn(string $urn): ?SupportTier
    {
        $urn = Urn::parse($urn, 'support-tier');

        if (!$urn || count($urn) !== 2) {
            throw new UserErrorException('Invalid URN', 400);
        }

        $supportTier = new SupportTier();
        $supportTier
            ->setEntityGuid($urn[0])
            ->setGuid($urn[1]);

        return $this->get($supportTier);
    }

    /**
     * Finds a matching Support Tier
     * @param SupportTier $matchingSupportTier
     * @return SupportTier|null
     * @throws \Minds\Exceptions\StopEventException
     */
    public function match(SupportTier $matchingSupportTier): ?SupportTier
    {
        $supportTiers = $this->repository->getList(
            (new RepositoryGetListOptions())
                ->setEntityGuid((string) $matchingSupportTier->getEntityGuid())
                ->setLimit(5000)
        );

        $supportTier = $supportTiers->filter(function (SupportTier $supportTier) use ($matchingSupportTier) {
            return
                    $supportTier->isPublic() === $matchingSupportTier->isPublic() &&
                    $supportTier->getUsd() === $matchingSupportTier->getUsd() &&
                    $supportTier->hasUsd() === $matchingSupportTier->hasUsd() &&
                    $supportTier->hasTokens() === $matchingSupportTier->hasTokens();
        })->first();

        if (!$supportTier) {
            return null;
        }

        return $this->hydrate(
            $supportTier
        );
    }

    /**
     * Creates a new Support Tier
     * @param SupportTier $supportTier
     * @return SupportTier|null
     * @throws \Minds\Exceptions\StopEventException
     */
    public function create(SupportTier $supportTier): ?SupportTier
    {
        $supportTier
            ->setGuid($this->guidBuilder->build());

        $success = $this->repository->add($supportTier);

        return $success ? $this->hydrate($supportTier) : null;
    }

    /**
     * Updates a Support Tier
     * @param SupportTier $supportTier
     * @return SupportTier|null
     * @throws \Minds\Exceptions\StopEventException
     */
    public function update(SupportTier $supportTier): ?SupportTier
    {
        $success = $this->repository->update($supportTier);

        return $success ? $this->hydrate($supportTier) : null;
    }

    /**
     * Deletes a Support Tier
     * @param SupportTier $supportTier
     * @return bool
     * @throws \Minds\Exceptions\StopEventException
     */
    public function delete(SupportTier $supportTier): bool
    {
        return $this->repository->delete($supportTier);
    }

    /**
     * Passes SupportTier to delegates
     * @param SupportTier $supportTier
     * @return SupportTier
     */
    protected function hydrate(SupportTier $supportTier): SupportTier
    {
        $delegates = [
            $this->currenciesDelegate,
            $this->paymentsDelegate,
        ];
        foreach ($delegates as $delegate) {
            $supportTier = $delegate->hydrate($supportTier);
        }
        return $supportTier;
    }
}
