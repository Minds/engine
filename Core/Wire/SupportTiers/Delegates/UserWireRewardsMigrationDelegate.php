<?php
namespace Minds\Core\Wire\SupportTiers\Delegates;

use Minds\Common\Repository\Response;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Wire\SupportTiers\Repository;
use Minds\Core\Wire\SupportTiers\RepositoryGetListOptions;
use Minds\Core\Wire\SupportTiers\SupportTier;
use Minds\Core\Wire\SupportTiers\TierBuilder;
use Minds\Entities\User;

/**
 * Handle User entity Wire Rewards conversion
 * @package Minds\Core\Wire\SupportTiers\Delegates
 */
class UserWireRewardsMigrationDelegate
{
    /** @var Config */
    protected $config;

    /** @var TierBuilder */
    protected $tierBuilder;

    /** @var Repository */
    protected $repository;

    /**
     * UserWireRewardsMigrationDelegate constructor.
     * @param $config
     * @param $tierBuilder
     * @param $repository
     */
    public function __construct(
        $config = null,
        $tierBuilder = null,
        $repository = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->tierBuilder = $tierBuilder ?: new TierBuilder();
        $this->repository = $repository ?: new Repository();
    }

    /**
     * Migrates a wire_reward structure into a SupportTier Response. If $write is true
     * it will write them to database.
     * @param User $user
     * @param bool $write
     * @return Response<SupportTier>
     * @throws \Exception
     */
    public function migrate(User $user, $write = false): Response
    {
        $wireRewards = $user->getWireRewards();
        $tokenExchangeRate = $this->config->get('token_exchange_rate') ?: 1.25;

        if (is_string($wireRewards)) {
            $wireRewards = json_decode($wireRewards, true);
        }

        // Conversion rate for merging with USD as base

        $types = [
            'money' => 1,
            'tokens' => $tokenExchangeRate,
        ];

        // Merge entries based on its amount converted to USD

        $entries = [];

        foreach ($types as $type => $exchangeRate) {
            foreach ($wireRewards['rewards'][$type] ?: [] as $reward) {
                $amount = $reward['amount'] ?? 0;

                if (!$amount || !is_numeric($amount)) {
                    continue;
                }

                $index = $amount * $exchangeRate * pow(10, 4);

                if (!isset($entries[$index])) {
                    $entries[$index] = [];
                }

                if (!isset($entries[$index]['descriptions'])) {
                    $entries[$index]['descriptions'] = [];
                }

                $entries[$index][$type] = $amount;
                $entries[$index]['descriptions'][] = $reward['description'];
            }
        }

        // Sort
        ksort($entries, SORT_NUMERIC);

        // Set Support Tiers

        $i = 0;
        $urns = [];

        $response = new Response();
        $response->setLastPage(true);

        foreach ($entries as $entry) {
            $usd = ($entry['money'] ?? 0) ?: ($entry['tokens'] * $tokenExchangeRate);

            $supportTier = new SupportTier();
            $supportTier
                ->setEntityGuid((string) $user->guid)
                ->setPublic(true)
                ->setName($this->tierBuilder->buildName($i))
                ->setDescription(trim(implode(' - ', $entry['descriptions'])))
                ->setUsd($usd)
                ->setHasUsd(isset($entry['money']))
                ->setHasTokens(isset($entry['tokens']));

            $supportTier->setGuid(
                (string) $this->tierBuilder->buildGuid($supportTier)
            );

            if ($write) {
                $this->repository->add($supportTier);
            }

            $urns[] = $supportTier->getUrn();
            $response[] = $supportTier;
            $i++;
        }

        // Delete removed Support Tiers

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

        // Response

        return $response;
    }
}
