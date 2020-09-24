<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Core\Monetization\Partners\Manager;
use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Entities;

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

        $daysAgo = $this->getOpt('daysAgo') ?: 0;
        $from = $this->getOpt('from') ?: strtotime("midnight $daysAgo days ago");
        $manager = new Manager();

        $i = 0;
        foreach ($manager->issueDeposits([ 'from' => $from ]) as $record) {
            ++$i;
            $usd = round($record->getAmountCents() / 100, 2);
            $this->out("[$i]: {$record->getItem()} $$usd");
        }
    }

    public function payout()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $manager = new Manager();

        $opts = [
            'to' => strtotime('midnight 31st August 2020') * 1000,
            'dryRun' => true,
        ];

        $i = 0;
        foreach ($manager->issuePayouts($opts) as $earningsPayout) {
            ++$i;
            $user = Di::_()->get('EntitiesBuilder')->single($earningsPayout->getUserGuid());
            $usd = ($earningsPayout->getAmountCents() / 100) ?: 0;
            echo "\n $i, {$earningsPayout->getUserGuid()}, {$usd}, {$earningsPayout->getMethod()}, $user->username";
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
