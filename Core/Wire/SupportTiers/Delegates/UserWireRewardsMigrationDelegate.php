<?php
namespace Minds\Core\Wire\SupportTiers\Delegates;

use Minds\Common\Repository\Response;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Guid;
use Minds\Core\Wire\SupportTiers\Repository;
use Minds\Core\Wire\SupportTiers\RepositoryGetListOptions;
use Minds\Core\Wire\SupportTiers\SupportTier;
use Minds\Core\Wire\SupportTiers\TierBuilder;
use Minds\Entities\User;
use Minds\Helpers\Log;

/**
 * Handle User entity Wire Rewards conversion (back and forth).
 * @package Minds\Core\Wire\SupportTiers\Delegates
 */
class UserWireRewardsMigrationDelegate
{
    /** @var Repository */
    protected $repository;

    /** @var Save */
    protected $saveAction;

    /** @var TierBuilder */
    protected $tierBuilder;

    /**
     * UserWireRewardsMigrationDelegate constructor.
     * @param $repository
     * @param $saveAction
     * @param $tierBuilder
     */
    public function __construct(
        $repository = null,
        $saveAction = null,
        $tierBuilder = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->saveAction = $saveAction ?: new Save();
        $this->tierBuilder = $tierBuilder ?: new TierBuilder();
    }

    /**
     * Migrates a wire_reward structure into a SupportTier Response. If $write is true
     * it will write them to database.
     * @param User $user
     * @param bool $write
     * @return Response<SupportTier>
     */
    public function migrate(User $user, $write = false): Response
    {
        $wireRewards = $user->getWireRewards();

        if (is_string($wireRewards)) {
            $wireRewards = json_decode($wireRewards, true);
        }

        $data = [
            'tokens' => $wireRewards['rewards']['tokens'] ?: [],
            'usd' => $wireRewards['rewards']['money'] ?: [],
        ];

        usort($data['tokens'], [$this->tierBuilder, 'sortRewards']);
        usort($data['usd'], [$this->tierBuilder, 'sortRewards']);

        $urns = [];

        $response = new Response();
        $response->setLastPage(true);

        foreach ($data as $currency => $rewards) {
            $i = 0;

            foreach ($rewards as $reward) {
                $amount = (float) $reward['amount'];

                $supportTier = new SupportTier();
                $supportTier
                    ->setEntityGuid((string) $user->guid)
                    ->setCurrency($currency)
                    ->setGuid(($reward['guid'] ?? '') ?: $this->tierBuilder->buildGuid($currency, $amount))
                    ->setAmount($amount)
                    ->setName(($reward['name'] ?? '') ?: $this->tierBuilder->buildName($i))
                    ->setDescription($reward['description'] ?: '');

                if ($write) {
                    $this->repository->add($supportTier);
                }

                $urns[] = $supportTier->getUrn();
                $response[] = $supportTier;
                $i++;
            }
        }

        if ($write) {
            $all = $this->repository->getList(
                (new RepositoryGetListOptions())
                    ->setEntityGuid((string) $user->guid)
            );

            $removedSupportTiers = $all->filter(function (SupportTier $supportTier) use ($urns) {
                return !in_array($supportTier->getUrn(), $urns, true);
            });

            foreach ($removedSupportTiers as $supportTier) {
                $this->repository->delete($supportTier);
            }
        }

        return $response;
    }

    /**
     * Creates a wire_rewards compatible output based on a SupportTier iterable
     * @param User $user
     * @return void
     * @throws \Minds\Exceptions\StopEventException
     */
    public function sync(User $user): void
    {
        $wireRewards = [
            'description' => '',
            'rewards' => [
                'tokens' => [],
                'money' => [],
            ]
        ];

        /** @var SupportTier[] $supportTiers */
        $supportTiers = $this->repository->getList(
            (new RepositoryGetListOptions())
                ->setEntityGuid((string) $user->guid)
                ->setLimit(5000)
        );

        foreach ($supportTiers as $supportTier) {
            $reward = [
                'guid' => (string) $supportTier->getGuid(),
                'amount' => (float) $supportTier->getAmount(),
                'name' => (string) $supportTier->getName(),
                'description' => (string) $supportTier->getDescription(),
            ];

            switch ($supportTier->getCurrency()) {
                case 'tokens':
                    $wireRewards['rewards']['tokens'][] = $reward;
                    break;

                case 'usd':
                    $wireRewards['rewards']['money'][] = $reward;
                    break;

                default:
                    Log::notice('Unknown support tier currency: ' . json_encode($supportTier));
                    break;
            }
        }

        $user
            ->setWireRewards($wireRewards);

        $this->saveAction
            ->setEntity($user)
            ->save();
    }
}
