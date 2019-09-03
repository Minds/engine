<?php

namespace Minds\Controllers\Cli;

use DateTime;
use Elasticsearch\ClientBuilder;
use Minds\Cli;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;
use Minds\Entities;
use Minds\Helpers\Flags;
use Minds\Interfaces;
use Minds\Core\Rewards\Contributions\UsersIterator;
use Minds\Core\Events\Dispatcher;

class Rewards extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        switch ($command) {
            case 'sync':
                $this->out('Get rewards for all users');
                $this->out('--timestamp={timestamp} the day to start from. Default is yesterday at midnight');
                $this->out('--action={active|subscribe|jury-duty} Type of action');
                $this->out('--dry-run={true|false} true prevents saving the data');
                break;
            case 'single':
                $this->out('Get rewards for a single user');
                $this->out('--guid={guid} the guid of the user to get rewards for');
                $this->out('--timestamp={timestamp} the day to start from. Default is yesterday at midnight');
                break;
            case 'issue':
                $this->out('Issue tokens to a user');
                $this->out('--username={string username} username of the user to issue tokens to');
                $this->out('--amount={number of tokens} Number of tokens to issue');
            default:
                $this->out('Syntax usage: cli rewards <type>');
                $this->displayCommandHelp();
        }
    }

    public function exec()
    {
        return $this->help();
    }

    public function sync()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $timestamp = $this->getOpt('timestamp') ?: (strtotime('midnight -24 hours') * 1000);
        $to = strtotime("+24 hours", $timestamp / 1000) * 1000;
        $dryRun = $this->getOpt('dry-run') === 'true';

        if ($dryRun) {
            $this->out('DRY RUN');
        }

        $users = new UsersIterator;
        $users->setFrom($timestamp)
            ->setTo($to);

        if ($action = $this->getOpt('action')) {
            $users->setAction($action);
        }

        $this->out("Getting rewards for all users");

        $total = BigNumber::_(0);
        $leaderboard = [];
        $i = 0;
        foreach ($users as $guid) {
            $i++;
            if (!$guid) {
                continue;
            }
            try {
                $user = new Entities\User((string) $guid, false);

                if (!$user->getPhoneNumberHash()) {
                    // Avoid users without a phone number hash
                    continue;
                }

                $manager = new Core\Rewards\Manager();
                $manager->setFrom($timestamp)
                    ->setTo($to);
                $manager->setUser($user);
                $manager->setDryRun($dryRun);
                $reward = $manager->sync();
                $total = $total->add($reward->getAmount());
                $leaderboard[$user->guid] = ($reward->getAmount() / (10**18));
                $amnt = (int) $reward->getAmount();
                if ($amnt > 0) {
                    Dispatcher::trigger('notification', 'reward', [
                        'to' => [$guid],
                        'from' => $guid,
                        'notification_view' => 'rewards_summary',
                        'params' => ['amount' => number_format($reward->getAmount() / (10**18), 3)],
                    ]);
                }
                echo "\n [$i][$guid]: synced past 48 hours. {$amnt}/$total";
            } catch (\Exception $e) {
                var_dump($e);
                echo "\n [$i][$guid]: failed synced past 48 hours. {$reward->getAmount()}/$total";
            }
        }
        
        $fp = fopen("contributions-{$timestamp}.csv", 'w');

        foreach ($leaderboard as $guid => $count) {
            fputcsv($fp, [ $guid, $count ]);
        }

        fclose($fp);
    }

    public function single()
    {
        $timestamp = $this->getOpt('timestamp') ?: (strtotime('midnight -24 hours') * 1000);
        $to = strtotime("+24 hours", $timestamp / 1000) * 1000;

        $manager = new Core\Rewards\Manager();
        $manager->setFrom($timestamp)
            ->setTo($to);
        
        $user = new Entities\User();
        $user->guid = (string) $this->getOpt('guid');
        $manager->setUser($user);
        $manager->setDryRun(true);
        $reward = $manager->sync();
    
        var_dump($reward);
    }

    public function issue()
    {
        $username = $this->getOpt('username');
        $user = new Entities\User($username);
        
        $amount = BigNumber::toPlain($this->getOpt('amount'), 18);

        $offChainTransactions = Di::_()->get('Blockchain\Wallets\OffChain\Transactions');
        $offChainTransactions
            ->setType('test')
            ->setUser($user)
            ->setAmount((string) $amount)
            ->create();

        $this->out('Issued');
    }
}
