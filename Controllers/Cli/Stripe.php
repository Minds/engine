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
}
