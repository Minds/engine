<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Entities;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Analytics\Iterators\SignupsOffsetIterator;
use Minds\Core\Boost\Network\Manager;
use Minds\Core\Util\BigNumber;
use Minds\Helpers\Counters;

class Stripe extends Cli\Controller implements Interfaces\CliControllerInterface
{
    private $db;
    private $es;
    private $elasticRepository;

    private $pendingBulkInserts = [];

    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        echo "1";
    }

    public function get_payment_intent()
    {
        $intent = new Core\Payments\Stripe\Intents\PaymentIntent();
        $intent->setAmount(2000);

        $intentManager = new Core\Payments\Stripe\Intents\Manager();
        $intent = $intentManager->add($intent);

        var_dump($intent);
    }

    public function get_setup_intent()
    {
        $intent = new Core\Payments\Stripe\Intents\SetupIntent();

        $intentManager = new Core\Payments\Stripe\Intents\Manager();
        $intent = $intentManager->add($intent);

        var_dump($intent->getClientSecret());
    }

    public function get_setup_intent_payment_method()
    {
        $id = $this->getOpt('id');

        $intentManager = new Core\Payments\Stripe\Intents\Manager();
        $intent = $intentManager->get($id);
        var_dump($intent);
    }

    public function fix_connect()
    {
        $connectManager = new Core\Payments\Stripe\Connect\Manager();
        $i = 0;
        foreach ($connectManager->getList() as $account) {
            ++$i;
            echo "\n$i $account->id";
            var_dump($account->requirements->currently_due);
        }
    }

    public function remove_business_type()
    {
        $connectManager = new Core\Payments\Stripe\Connect\Manager();
        $account = $connectManager->getByAccountId($this->getOpt('id'));
        $connectManager->update($account);
    }

    public function create_stripe_lookups()
    {
        $connectManager = new Core\Payments\Stripe\Connect\Manager();
        $iterator = new Core\Analytics\Iterators\SignupsOffsetIterator();
        $iterator->token = $this->getOpt('token');
        $i = 0;
        $s = 0;
        foreach ($iterator as $user) {
            if (!$user instanceof Entities\User) {
                continue;
            }
            ++$i;
            var_dump($user->getMerchant());
            if ($stripeId = $user->getMerchant()['id']) {
                ++$s;
            }
            echo "\n$s/$i $user->guid {$stripeId} ($iterator->token)";
            if (!$stripeId) {
                continue;
            }
            try {
                $account = $connectManager->getByAccountId($stripeId);
                $account->setEmail($user->getEmail());
                $account->setUrl('https://www.minds.com/' . $user->username);
                $account->setMetadata([
                    'guid' => (string) $user->guid,
                ]);
                $connectManager->update($account);
            } catch (\Exception $e) {
            }
        }
    }
}
