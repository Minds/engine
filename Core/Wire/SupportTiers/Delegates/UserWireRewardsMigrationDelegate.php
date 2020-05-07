<?php
namespace Minds\Core\Wire\SupportTiers\Delegates;

use Minds\Common\Repository\Response;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Guid;
use Minds\Core\Wire\SupportTiers\Repository;
use Minds\Core\Wire\SupportTiers\RepositoryGetListOptions;
use Minds\Core\Wire\SupportTiers\SupportTier;
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

    /**
     * UserWireRewardsMigrationDelegate constructor.
     * @param $repository
     */
    public function __construct(
        $repository = null,
        $saveAction = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->saveAction = $saveAction ?: new Save();
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

        if (!$wireRewards) {
            return new Response([]);
        }

        $data = [
            'tokens' => $wireRewards['rewards']['tokens'] ?: [],
            'usd' => $wireRewards['rewards']['money'] ?: [],
        ];

        $response = new Response();
        $response->setLastPage(true);

        foreach ($data as $currency => $rewards) {
            foreach ($rewards as $reward) {
                $supportTier = new SupportTier();
                $supportTier
                    ->setEntityGuid((string) $user->guid)
                    ->setCurrency($currency)
                    ->setGuid(Guid::build())
                    ->setAmount((float) $reward['amount'])
                    ->setName($reward['description'])
                    ->setDescription('');

                if ($write) {
                    $this->repository->add($supportTier);
                }

                $response[] = $supportTier;
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
                'amount' => (float) $supportTier->getAmount(),
                'description' => (string) $supportTier->getName(),
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
