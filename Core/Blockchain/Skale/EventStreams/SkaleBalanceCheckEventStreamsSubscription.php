<?php

namespace Minds\Core\Blockchain\Skale\EventStreams;

use Minds\Core\Blockchain\EventStreams\BlockchainTransactionEvent;
use Minds\Core\Blockchain\EventStreams\BlockchainTransactionsTopic;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\BalanceSynchronizer;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\SyncExcludedUserException;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Log\Logger;
use Minds\Entities\User;

/**
 * Subscribes to events requesting Skale balance to be checked against offchain balance.
 * Will output error logs if there is an unexpected offset between balances.
 */
class SkaleBalanceCheckEventStreamsSubscription implements SubscriptionInterface
{
    /** @var int How often a balance sync check should be made for a user. Configurable - defaults to 6 hours. */
    private $balanceSyncFrequency = 21600;

    /** @var array - array containing users already checked of the format `user_guid => timestamp.` */
    private static $alreadyChecked = [];

    /**
     * Constructor.
     * @param BalanceSynchronizer|null $balanceSynchronizer - used to check balance is in sync.
     * @param EntitiesBuilder|null $entitiesBuilder - builds entities from guid.
     * @param ExperimentsManager|null $experiments - checks whether feature flag is active.
     * @param Logger|null $logger - log output.
     * @param Config|null $config - get configuration.
     */
    public function __construct(
        private ?BalanceSynchronizer $balanceSynchronizer = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?ExperimentsManager $experiments = null,
        private ?Logger $logger = null,
        private ?Config $config = null
    ) {
        $this->balanceSynchronizer ??= Di::_()->get('Blockchain\Skale\BalanceSynchronizer');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->experiments ??= Di::_()->get('Experiments\Manager');
        $this->logger ??= Di::_()->get('Logger');
        $this->config ??= Di::_()->get('Config');

        if ($balanceSyncFrequency = $this->config->get('blockchain')['skale']['balance_sync_check_frequency_seconds'] ?? false) {
            $this->balanceSyncFrequency = $balanceSyncFrequency;
        }
    }

    /**
     * Get subscription id.
     * @return string - subscription id.
     */
    public function getSubscriptionId(): string
    {
        return 'skale_balance_checks';
    }

    /**
     * Gets topic - a new BlockchainTransactionsTopic instance.
     * @return TopicInterface - topic.
     */
    public function getTopic(): TopicInterface
    {
        return new BlockchainTransactionsTopic();
    }

    /**
     * Gets regex to filter topics.
     * @return string - regex to filter topics.
     */
    public function getTopicRegex(): string
    {
        return '.*'; // Get all.
    }

    /**
     * Called when there is a new event.
     * @param EventInterface $event - event containing transaction data.
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof BlockchainTransactionEvent) {
            return false;
        }

        if (!$this->experiments->isOn('engine-2360-skale-balance-sync')) {
            $this->logger->warn("Feature engine-2360-skale-balance-sync is off");
            return false;
        }

        $senderGuid = $event->getSenderGuid();
        $sender = $this->entitiesBuilder->single($senderGuid);

        if (!$sender || !$sender instanceof User) {
            return true; // no user - we don't want to trigger this again.
        }

        if ($this->hasBeenCheckedAlready($senderGuid)) {
            return true;
        }
        
        try {
            $adjustmentResult = $this->balanceSynchronizer
                ->withUser($sender)
                ->sync(dryRun: true);
        } catch (SyncExcludedUserException $e) {
            return true;
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        if ($adjustmentResult) {
            $this->logger->error($adjustmentResult);
        }

        self::$alreadyChecked[$senderGuid] = time();

        return true;
    }

    /**
     * Set already checked - built initially for use in
     * spec tests to reset the static private members value.
     * @param array $value - value to set self::$alreadyChecked to.
     * @return self
     */
    public function setAlreadyChecked(array $value): self
    {
        self::$alreadyChecked = $value;
        return $this;
    }

    /**
     * Whether user has been checked already in defined timespan.
     * @param string $userGuid - user guid to check.
     * @return boolean - true if user has been checked already, else false.
     */
    private function hasBeenCheckedAlready(string $userGuid): bool
    {
        if (array_key_exists($userGuid, self::$alreadyChecked)) {
            $lastChecked = self::$alreadyChecked[$userGuid];
            if ((time() - $lastChecked) < $this->balanceSyncFrequency) {
                return true; // already checked.
            }
            unset(self::$alreadyChecked[$userGuid]);
        }
        return false;
    }
}
