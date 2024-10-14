<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Onchain;

use Exception;
use Minds\Core\Blockchain\Services\Ethereum as EthereumService;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Manager;
use Minds\Core\Boost\V3\Repository;
use Minds\Core\Boost\V3\PreApproval\Manager as PreApprovalManager;
use Minds\Core\Config\Config;
use Minds\Core\Util\BigNumber;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Entities\User;

/**
 * Designed to be be run  periodically in the background.
 * This class will check onchain boosts and alter their states.
 */
class OnchainBoostBackgroundJob
{
    /** @var string */
    const BOOST_SENT_TOPIC = "0x68170a430a4e2c3743702c7f839f5230244aca61ed306ec622a5f393f9559040";

    public function __construct(
        private ?Manager $manager = null,
        private ?Repository $repository = null,
        private ?PreApprovalManager $preApprovalManager = null,
        private ?EthereumService $ethereumService = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Config $config = null,
        private ?Logger $logger = null
    ) {
        $this->manager ??= Di::_()->get(Manager::class);
        $this->repository ??= Di::_()->get(Repository::class);
        $this->preApprovalManager ??= Di::_()->get(PreApprovalManager::class);
        $this->ethereumService ??= Di::_()->get('Blockchain\Services\Ethereum');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->config ??= Di::_()->get('Config');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Designed to be called from a CLI script in Cli/Blockchain
     * Will cycle through pending onchain boosts and confirm via the blockchain.
     */
    public function run()
    {
        // Get boosts in PENDING_ONCHAIN_CONFIRMATION state
        $pendingOnchainConfirmations = $this->repository->getBoosts(
            limit: 12,
            offset: 0,
            targetStatus: BoostStatus::PENDING_ONCHAIN_CONFIRMATION
        );

        foreach ($pendingOnchainConfirmations as $boost) {
            try {
                $tx = $boost->getPaymentTxId();

                // Check the information from Ethereum
                $receipt = $this->ethereumService->request('eth_getTransactionReceipt', [ $tx ]);

                if (!$receipt) {
                    if ($boost->getCreatedTimestamp() > strtotime('48 hours ago')) {
                        $this->log($boost, "Receipt not found. Skipping as the boost is not 48 hours old yet.");
                    } else {
                        $this->fail($boost, "Transaction not found");
                    }
                    continue;
                }

                // See if the tx was accepted or not
                if ($receipt['status'] === '0x1') {
                    $logs = array_filter($receipt['logs'], function ($log) {
                        return $log['topics'][0] === self::BOOST_SENT_TOPIC;
                    });
                
                    // If there are no logs, something is wrong. Fail.
                    if (empty($logs)) {
                        $this->fail($boost, "Could not find the relevant log for the boostSent topic.");
                    }

                    $log = array_values($logs)[0];

                    // If the contract address isn't what we expect, something is wrong. Fail.
                    if (strtolower($log['address']) !== strtolower($this->config->get('blockchain')['contracts']['boost']['contract_address'])) {
                        $this->fail($boost, "$tx Wrong contract address.");
                    }
                
                    $guidHex = $log['data'];
                    $guid = BigNumber::fromHex($guidHex)->toString();

                    // If the guid on the blockchain doesn't match, something is wrong. Fail.
                    if ($boost->getGuid() !== $guid) {
                        $this->fail($boost, "Guid ($guid) does not match.");
                    }

                    // Everything is good from the blockchain side. Accept.
                    $this->accept($boost);
                } else {
                    $this->fail($boost, "Transaction failed.");
                }
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Accept the boost
     * @param Boost $boost
     * @return void
     */
    private function accept(Boost $boost): void
    {
        $boostOwner = $this->entitiesBuilder->single($boost->getOwnerGuid());

        if (!$boostOwner || !($boostOwner instanceof User)) {
            $this->fail($boost, "Owner is not found.");
            return; // Not neccessary but static analysis being weird
        }

        try {
            // First move to pending.
            $this->manager->updateStatus($boost->getGuid(), BoostStatus::PENDING);
            $this->log($boost, "Moved to pending.");

            // // If eligible, auto approve
            // if ($this->preApprovalManager->shouldPreApprove($boostOwner)) {
            //     $this->manager->approveBoost($boost->getGuid());
            //     $this->log($boost, "Auto approved.");
            // }
        } catch (\Exception $e) {
            $this->log($boost, "Unexpected error: {$e->getMessage()}.");
        }
    }

    /**
     * Helper function to mark a boost as failed and log
     * @param Boost $boost
     * @param string $reason
     * @return void
     * @throws Exception
     */
    private function fail(Boost $boost, string $reason): void
    {
        $this->manager->updateStatus($boost->getGuid(), BoostStatus::FAILED);
        $this->log($boost, "{$boost->getPaymentTxId()} $reason Marking as failed state.");
        throw new Exception();
    }

    /**
     * Helper function to log the message in a common format
     * @param Boost $boost
     * @param string $message
     * @return void
     */
    private function log(Boost $boost, string $message): void
    {
        $this->logger->info("{$boost->getGuid()} $message");
    }
}
