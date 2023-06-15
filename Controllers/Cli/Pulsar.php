<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Interfaces;

class Pulsar extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
        error_reporting(E_ALL);
    }
    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    public function exec()
    {
    }

    public function pong()
    {
        $actionsEventsTopic = new ActionEventsTopic();
        $actionsEventsTopic->consume(
            subscriptionId: 'ping-pong',
            callback: function ($message) {
                echo "new Message";
                var_dump($message->getDataAsString());
                return true;
            },
            topicRegex: 'ping',
            isBatch: false,
            batchTotalAmount: 1,
            execTimeoutInSeconds: 30,
            onBatchConsumed: function ($abc) {
            }
        );
    }


    public function ping()
    {
        while (true) {
            $event = new ActionEvent();
            $event
                ->setAction('ping')
                ->setUser(new User())
                ->setEntity(new Activity());

            $actionsEventsTopic = new ActionEventsTopic();
            if ($actionsEventsTopic->send($event)) {
                $this->out('sent');
            } else {
                $this->out('failed');
            }
            sleep(10);
        }
    }
}
