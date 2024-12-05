<?php
declare(ticks = 1);

/**
 * Blockchain CLI
 *
 * @author emi
 */

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Blockchain\EthPrice;
use Minds\Core\Blockchain\Events\BoostEvent;
use Minds\Core\Blockchain\OnchainBalances\OnchainBalancesService;
use Minds\Core\Blockchain\Services;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Interfaces;
use Minds\Core\Util\BigNumber;

class Blockchain extends Cli\Controller implements Interfaces\CliControllerInterface
{
    protected $ethActiveFilter;

    /**
     * Echoes $commands (or overall) help text to standard output.
     * @param  string|null $command - the command to be executed. If null, it corresponds to exec()
     * @return null
     */
    public function help($command = null)
    {
        $this->out('Usage: cli blockchain [listen]');
    }

    /**
     * Executes the default command for the controller.
     * @return mixed
     */
    public function exec()
    {
        $this->help();
    }

    public function listen()
    {
        if (function_exists('pcntl_signal')) {
            // Intercept Ctrl+C

            pcntl_signal(SIGINT, function () {
                $this->filterCleanup();
                exit;
            });
        }

        \Minds\Core\Events\Defaults::_();

        $ethereum = Di::_()->get('Blockchain\Services\Ethereum');

        $topics = Dispatcher::trigger('blockchain:listen', 'all', [], []);
        $filterOptions = [
            'topics' => [ array_keys($topics) ] // double array = OR
        ];

        $from = $this->getOpt('from');

        if ($from) {
            $filterOptions['fromBlock'] = $from;
        }

        $filterId = $ethereum
            ->request('eth_newFilter', [ $filterOptions ]);

        if (!$filterId) {
            $this->out('Filter could not be set');
            exit(1);
        }

        $this->ethActiveFilter = $filterId;

        while (true) {
            $logs = $ethereum
                ->request('eth_getFilterChanges', [ $filterId ]);

            if (!$logs) {
                sleep(1);
                continue;
            }

            foreach ($logs as $log) {
                $namespace = 'all';

                $this->out('Block ' . $log['blockNumber']);

                if (!isset($log['topics'])) {
                    $this->out('No topics. Skipping…');
                    continue;
                }

                foreach ($log['topics'] as $topic) {
                    if (isset($topics[$topic])) {
                        try {
                            (new $topics[$topic]())->event($topic, $log);
                        } catch (\Exception $e) {
                            $this->out('[Topic] ' . $e->getMessage());
                            continue;
                        }
                    }
                }
            }
            
            usleep(500 * 1000); // 500ms
        }

        $this->filterCleanup();
    }

    protected function filterCleanup()
    {
        $ethereum = Di::_()->get('Blockchain\Services\Ethereum');

        if ($this->ethActiveFilter) {
            $done = $ethereum
                ->request('eth_uninstallFilter', [ $this->ethActiveFilter ]);

            if ($done) {
                $this->out(['', 'Cleaned up filter…', $this->ethActiveFilter]);
            }

            $this->ethActiveFilter = null;
        }
    }

    public function balance()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $username = $this->getOpt('username');
        $user = new \Minds\Entities\User($username);
        var_dump($user);
        $offChainBalance = Di::_()->get('Blockchain\Wallets\OffChain\Balance');
        $offChainBalance->setUser($user);
        $offChainBalanceVal = BigNumber::_($offChainBalance->get());
        $this->out((string) $offChainBalanceVal);
    }

    public function uniswap_user()
    {
        $username = $this->getOpt('username');
        $user = new \Minds\Entities\User($username);
        $address = '0x177fd9efd24535e73b81e99e7f838cdef265e6cb';

        $uniswap = Di::_()->get('Blockchain\Uniswap\Client');
        $response = $uniswap->getUser($address);

        var_dump($response);
    }

    public function uniswap_mints()
    {
        $pairIds = Di::_()->get('Config')->get('blockchain')['liquidity_positions']['approved_pairs'];

        $uniswap = Di::_()->get('Blockchain\Uniswap\Client');
        $response = $uniswap->getMintsByPairIds($pairIds);
    
        var_dump($response);
    }

    public function liquidity_share()
    {
        $username = $this->getOpt('username');
        $user = new \Minds\Entities\User($username);

        $liquidityManager = Di::_()->get('Blockchain\LiquidityPositions\Manager')
            ->setUser($user);

        var_dump($liquidityManager->getLiquidityTokenShare());
    }

    public function liquidity_providers_summaries()
    {
        $liquidityManager = Di::_()->get('Blockchain\LiquidityPositions\Manager');
        $summaries = $liquidityManager->getAllProvidersSummaries();
        var_dump($summaries);
    }

    public function syncMetrics()
    {
        Di::_()->get('Config')
            ->set('min_log_level', 'INFO');

        $hoursAgo = $this->getOpt('hoursAgo') ?? "0";
        $to = strtotime("$hoursAgo hours ago", time());
        $from = strtotime('24 hours ago', $to);

        $metricManager = Di::_()->get('Blockchain\Metrics\Manager');
        $metricManager
            ->setTimeBoundary($from, $to)
            ->sync();
    }

    /**
     * Will sync blocks from etherscan to our cassandra table
     */
    public function syncBlocks()
    {
        /** @var Services\BlockFinder */
        $blockFinder = Di::_()->get('Blockchain\Services\BlockFinder');
        
        /** @var int */
        $interval = $this->getOpt('interval') ?: 10;
        
        while (true) {
            $unixTimestamp = time();
            $blockNumber = $blockFinder->getBlockByTimestamp($unixTimestamp, false);
            $date = date('c', $unixTimestamp);
            $this->out("[$date]: Block Number: $blockNumber");
            sleep($interval);
        }
    }

    /**
     * Trigger a boost event.
     * ! Only currently supports V3 boosts. !
     * @param string eventType - resolve or fail.
     * @param string boostGuid - guid of the boost - NOT entity GUID.
     * @return void
     */
    public function triggerBoostEvent(): void
    {
        $eventType = $this->getOpt('eventType') ?? 'resolve';
        $boostGuid = $this->getOpt('boostGuid') ?? false;

        if (!$eventType || !$boostGuid) {
            $this->out('Must supply valid event type and boostGuid');
        }
        
        $boostEvent = new BoostEvent();

        $transaction = (new Transaction())
            ->setContract('boost')
            ->setData(['guid' => $boostGuid]);

        if ($eventType === 'fail') {
            $boostEvent->boostFail(null, $transaction);
        } elseif ($eventType === 'resolve') {
            $boostEvent->boostSent(null, $transaction);
        } else {
            $this->out('Unsupported event type - only `fail` and `resolve` are currently supported');
        }
    }

    public function iterate_token_holders()
    {
        /** @var OnchainBalancesService */
        $onchainBalancesService = Di::_()->get(OnchainBalancesService::class);

        foreach ($onchainBalancesService->getAll() as $account) {
            var_dump($account);
        }
    }
}
