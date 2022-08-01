<?php

namespace Minds\Controllers\Cli;

use Brick\Math\BigDecimal;
use DateTime;
use Elasticsearch\ClientBuilder;
use Minds\Cli;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Minds;
use Minds\Core\Util\BigNumber;
use Minds\Entities;
use Minds\Helpers\Flags;
use Minds\Interfaces;
use Minds\Core\Rewards\Contributions\UsersIterator;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Rewards\RewardsQueryOpts;
use Minds\Entities\User;

class Rewards extends Cli\Controller implements Interfaces\CliControllerInterface
{
    private $start;
    private $elasticsearch;

    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    public function exec()
    {
    }

    public function sync()
    {
        Di::_()->get('Config')
            ->set('min_log_level', 'INFO');

        ini_set('memory_limit', '1G'); // Temporary hack as the caches grow too big
 
        $timestamp = $this->getOpt('timestamp') ?: (strtotime('midnight -24 hours'));
        $dryRun = $this->getOpt('dry-run') ?: false;
        $recalculate = $this->getOpt('recalculate') ?: false;

        $opts = new RewardsQueryOpts();
        $opts->setDateTs($timestamp);

        $manager = Di::_()->get('Rewards\Manager');

        if ($recalculate) {
            $this->out('-- Recalculating the rewards --');
            $manager->calculate($opts);
            sleep(30); // Sleeping for 30 seconds
        }

        $this->out('-- Issuing the tokens --');
        $manager->issueTokens($opts, $dryRun);
    }

    /**
     * Mint offchain tokens and send them to a given username.
     * Can bypass SKALE mirror to manually set offchain token balance.
     * @return void
     */
    public function mint()
    {
        $username = $this->getOpt('username');
        $bypassSkaleMirror = $this->getOpt('bypassSkaleMirror') ?? false;

        $amount = BigNumber::toPlain($this->getOpt('amount'), 18);

        $user = Di::_()->get('EntitiesBuilder')->getByUserByIndex($username);

        if (!$user || !($user instanceof User)) {
            $this->out('Unable to construct user');
            return;
        }

        $offChainTransactions = Di::_()->get('Blockchain\Wallets\OffChain\Transactions');
        $offChainTransactions
            ->setType('test')
            ->setUser($user)
            ->setAmount((string) $amount)
            ->setBypassSkaleMirror($bypassSkaleMirror)
            ->setData([
                'amount' => (string) $amount,
                'receiver_guid' => (string) $user->getGuid(),
                'context' => 'direct_credit'
            ])
            ->create();

        $this->out('Issued');
    }

    public function add_reward()
    {
        $daysAgo = $this->getOpt('daysAgo') ?: 0;
        $dateTs = strtotime("midnight $daysAgo days ago");

        $userGuid = $this->getOpt('user_guid');
        $rewardType = $this->getOpt('reward_type') ?: 'engagement';
        $score = $this->getOpt('score') ?: 0;

        $rewardEntry = new Core\Rewards\RewardEntry();
        $rewardEntry->setUserGuid($userGuid)
            ->setRewardType($rewardType)
            ->setDateTs($dateTs)
            ->setScore($score)
            ->setMultiplier(1);

        $manager = new Core\Rewards\Manager();
        $manager->add($rewardEntry);
    }

    public function get_list()
    {
        $userGuid = $this->getOpt('user_guid');

        $opts = new Core\Rewards\RewardsQueryOpts();
        $opts->setUserGuid($userGuid)
            ->setDateTs(time());

        $repository = new Core\Rewards\Repository();
        foreach ($repository->getList($opts) as $rewardEntry) {
            var_dump($rewardEntry->export());
        }
    }

    public function calculate()
    {
        Di::_()->get('Config')
            ->set('min_log_level', 'INFO');

        ini_set('memory_limit', '1G'); // Temporary hack as the caches grow too big

        $daysAgo = $this->getOpt('daysAgo') ?: 0;
        $dateTs = strtotime("midnight $daysAgo days ago");

        $opts = new Core\Rewards\RewardsQueryOpts();
        $opts->setDateTs($dateTs);

        $manager = new Core\Rewards\Manager();
        $manager->calculate($opts);
    }

    public function notify()
    {
        Di::_()->get('Config')
            ->set('min_log_level', 'INFO');
        $notify = Di::_()->get('Rewards\Notify');
        $notify->run();
    }
}
