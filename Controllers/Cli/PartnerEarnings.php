<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Monetization\Partners\Manager;
use Minds\Entities;
use Minds\Interfaces;

class PartnerEarnings extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $this->out('Missing subcommand');
    }

    public function sync()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        Di::_()->get('Config')->set('min_log_level', 'INFO');

        $daysAgo = $this->getOpt('daysAgo') ?: 0;
        $from = $this->getOpt('from') ?: strtotime("midnight $daysAgo days ago");
        $to = $this->getOpt('to') ?? (strtotime("tomorrow", $from) -1);

        // Dry Run option to test execution before applying changes
        $dryRun = (bool) $this->getOpt('dry-run') ?? false;
        $manager = new Manager();

        $i = 0;
        foreach ($manager->issueDeposits([ 'from' => $from, 'to' => $to, 'dry-run' => $dryRun ]) as $record) {
            ++$i;
            $usd = round($record->getAmountCents() / 100, 2);
            $tokens = round($record->getAmountTokens(), 3);
            $this->out("[$i]: {$record->getUserGuid()} {$record->getItem()} $$usd | $tokens tokens");
        }
    }

    public function payout()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $manager = new Manager();

        $opts = [
            'to' => strtotime('midnight last day of last month') * 1000,
            'dryRun' => true,
        ];

        $i = 0;
        foreach ($manager->issuePayouts($opts) as $earningsPayout) {
            ++$i;
            $user = Di::_()->get('EntitiesBuilder')->single($earningsPayout->getUserGuid());
            $usd = ($earningsPayout->getAmountCents() / 100) ?: 0;
            echo "\n $i, {$earningsPayout->getUserGuid()},  $user->username, , {$usd}, {$earningsPayout->getMethod()}, {$user->getPlusMethod()}, {$user->getProMethod()}";
        }
        echo "\nDone";
    }

    public function resetRpm()
    {
        $user = new Entities\User(strtolower($this->getOpt('username')));

        if (!$user->guid) {
            exit;
        }
        var_dump($user->getPartnerRpm());

        $manager = new Manager();
        //        $manager->resetRpm($user, $this->getOpt('rpm') ?: 1);

        $daysAgo = 180;
        //        var_dump($user->getPartnerRpm());

        while (--$daysAgo >= 0) {
            $this->out($daysAgo);
            foreach ($manager->issueDeposits([
                'from' => strtotime("midnight $daysAgo days ago"),
                'user_guid' => $user->getGuid(),
            ]) as $output) {
            };
        }
        $this->out('done');
    }
}
