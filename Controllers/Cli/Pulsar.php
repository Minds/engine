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
    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    public function exec()
    {
    }

    public function consumeActions()
    {
        $actionsEventsTopic = new ActionEventsTopic();
        $actionsEventsTopic->consume($this->getOpt('id'), function ($message) {
            var_dump($message->getDataAsString());
            return true;
        });
    }


    public function produceActions()
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
    //

    public function listen()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $client = new \Pulsar\Client("pulsar://pulsar:6650");
        $config = new \Pulsar\ConsumerConfiguration();
        $config->setConsumerType(\Pulsar\Consumer::ConsumerShared);

        $consumer = $client->subscribe("persistent://prop/r1/ns1/test-topic", "consumer-1", $config);

        while (true) {
            $message = $consumer->receive();
            //var_dump($message);
            var_dump($message->getDataAsString());
            $consumer->acknowledge($message);
        }
    }

    public function ping()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $client = new \Pulsar\Client("pulsar://pulsar:6650");
        $producer = $client->createProducer("persistent://prop/r1/ns1/test-topic");

        $prop = [
            "a" => 1,
        ];

        while (true) {
            $prop = [
                "hello" => 'world',
            ];

            $builder = new \Pulsar\MessageBuilder();
            $builder->setContent("Ping/Pong " . time())
                    ->setProperties($prop);

            $message = $builder->setDeliverAfter(1)->build();
            $producer->send($message);
            sleep(5);
        }
    }
}
