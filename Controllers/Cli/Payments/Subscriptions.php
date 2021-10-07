<?php

namespace Minds\Controllers\Cli\Payments;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Payments\Subscriptions\Manager;
use Minds\Core\Payments\Subscriptions\Queue;
use Minds\Core\Security\ACL;
use Minds\Helpers\Cql;
use Minds\Interfaces;
use Minds\Core\Util\BigNumber;

class Subscriptions extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        $this->out('Syntax usage: payments subscriptions [run]');
    }

    public function exec()
    {
        $this->help();
    }

    public function run()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Initialize events
        \Minds\Core\Events\Defaults::_();

        ACL::$ignore = true; // we need to save to channels

        /** @var Manager $manager */
        $manager = Di::_()->get('Payments\Subscriptions\Manager');

        /** @var Queue $queue */
        $subscriptions = Di::_()->get('Payments\Subscriptions\Iterator');
        $subscriptions
            ->setFrom(time())
            //->setFrom(strtotime('+2 days'))
            ->setPaymentMethod($this->getOpt('method') ?: 'tokens')
            //->setPaymentMethod('usd')
            ->setPlanId('wire');

        $this->out($this->getOpt('method') ?: 'tokens');

        foreach ($subscriptions as $subscription) {
            if ($subscription->getInterval() === 'once') {
                error_log('Ooops, this should be monthly not set to once');
                continue;
            }
            $this->out("Subscription:`{$subscription->getId()}`", static::OUTPUT_PRE);
            $billing = date('d-m-Y', $subscription->getNextBilling());
            
            $user_guid = $subscription->getUser()->guid;
            $user = Di::_()->get('EntitiesBuilder')->single($user_guid);

            if (!$user) {
                $this->out("\t User not found", static::OUTPUT_INLINE);
                continue;
            }

            // TODO move this to public api
            if ($user->getProExpires() === 0 && $subscription->getEntity()->getGuid() == '1030390936930099216') {
                $this->out("\t CANCELLED");
                $manager->cancelSubscriptions($user->getGuid(), '1030390936930099216');
                continue;
            }

            $this->out("\t$billing | $user_guid");

            $canCharge = true;
            foreach ($this->getPreviousPayments($user_guid) as $charge) {
                $date = date('c', $charge->created);
                $this->out("\t\t $charge->id on $date $charge->created");

                if ($charge->created > strtotime('10 days ago')) {
                    //$canCharge = false;
                    //continue;
                }
            }

            if (!$this->getOpt('dry-run') && $canCharge) {
                $this->out("\t CHARGED");
                $manager->setSubscription($subscription);
                $manager->charge();
            }
        }
        
        $this->out("Done");
    }

    public function fixProSubscriptions()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        ACL::$ignore = true; // we need to save to channels

        /** @var Manager $manager */
        $manager = Di::_()->get('Payments\Subscriptions\Manager');

        foreach (['usd', 'tokens'] as $method) {
            $this->out("Syncing $method");
            $subscriptions = Di::_()->get('Payments\Subscriptions\Iterator');
            $subscriptions->setFrom(strtotime('+40 days'))
                ->setPaymentMethod($method)
                ->setPlanId('wire');

            foreach ($subscriptions as $subscription) {
                if ($subscription->getEntity()->getGuid() != '1030390936930099216') {
                    //         $this->out("{$subscription->getEntity()->getGuid()}");
                    continue;
                }
                
                if ($subscription->getInterval() === 'once') {
                    error_log('Ooops, this should be monthly not set to once');
                    error_log($subscription->getEntity()->getGuid());
                    continue;
                }

                $this->out("Subscription:`{$subscription->getId()}`");
                $billing = date('d-m-Y', $subscription->getNextBilling());

                $user_guid = $subscription->getUser()->guid;
                $user = Di::_()->get('EntitiesBuilder')->single($user_guid);

                if (!$user) {
                    $this->out("\t User $user_guid not found!");
                    continue;
                }

                if ($user->getProExpires() === 0) {
                    continue;
                }

                // TODO move this to public api
                if ($user->getProExpires() < $subscription->getLastBilling() && $subscription->getEntity()->getGuid() == '1030390936930099216') {
                    $this->out("\t PRO subscription did not sync {$user->getProExpires()} is less than {$subscription->getLastBilling()}");
                    Di::_()->get('Pro\Manager')
                        ->setUser($user)
                        ->enable($subscription->getNextBilling());
                    continue;
                } else {
                    $this->out("\t subscription looks fine");
                }
            }
        }
    }

    public function repair()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        /** @var Manager $manager */
        $manager = Di::_()->get('Payments\Subscriptions\Manager');

        /** @var Queue $queue */
        $subscriptions = Di::_()->get('Payments\Subscriptions\Iterator');
        $subscriptions->setFrom(0)
            ->setPaymentMethod('tokens')
            ->setPlanId('wire');

        foreach ($subscriptions as $subscription) {
            $this->out("Subscription:`{$subscription->getId()}`");
            if ($subscription->getId() === 'offchain') {
                $this->out("Subscription:`{$subscription->getId()}` needs repairing");

                $urn = "urn:subscription:" . implode('-', [
                    $subscription->getId(),
                    $subscription->getUser()->getGuid(),
                    $subscription->getEntity()->getGuid(),
                ]);
                $this->out("Subscription:`{$subscription->getId()}` needs repairing to $urn");
                $manager->setSubscription($subscription);
                $manager->cancel();
                $subscription->setId($urn);
                $manager->setSubscription($subscription);
                $manager->create();
            }
            if (strpos($subscription->getId(), '0x', 0) === 0) {
                $this->out("Subscription:`{$subscription->getId()}` needs repairing");
                $urn = "urn:subscription:" . implode('-', [
                    $subscription->getId(),
                    $subscription->getUser()->getGuid(),
                    $subscription->getEntity()->getGuid(),
                ]);

                $this->out("Subscription:`{$subscription->getId()}` needs repairing to $urn");
                $manager->setSubscription($subscription);
                $manager->cancel();
                $subscription->setId($urn);
                $manager->setSubscription($subscription);
                $manager->create();
            }
        }

        $this->out("Done");
    }

    /**
     * Sometimes, plus doesn't hit the delegate so the badge
     * doesn't apply. This is designed to run regularly via
     * a cron job to fix that
     * @return void
     */
    public function fixPlusWires()
    {
        ACL::$ignore = true; // we need to save to channels
        $delegate = new \Minds\Core\Wire\Delegates\UpgradesDelegate;
        $usersLastPlus = []; //730071191229833224
        foreach ($this->getWires([1030390936930099216, 730071191229833224]) as $wire) {
            $sender_guid = $wire->getSender()->getGuid();
            $receiverGuid = $wire->getReceiver()->getGuid();
            $friendly = date('d-m-Y', $wire->getTimestamp());
            echo "\n$sender_guid->$receiverGuid";
            if ($wire->getTimestamp() < $usersLastPlus[$receiverGuid][$sender_guid] ?? time()) {
                echo " $friendly already given plus to this user";
                continue;
            }
            $usersLastPlus[$receiverGuid][$sender_guid] = $wire->getTimestamp();
            $friendly = date('d-m-Y', $wire->getTimestamp());
            echo " $friendly running wire delegate ({$wire->getAmount()})";

            if ($delegate->onWire($wire, 'offchain')
                 || $delegate->onWire($wire, '0x6f2548b1bee178a49c8ea09be6845f6aeaf3e8da')
                 || $delegate->onWire($wire, '0x97749850000766D54f882406Adc9d47458b2C137')
            ) {
                echo " done";
            }
        }
    }

    public function getWires($userGuids = [])
    {
        $cql = \Minds\Core\Di\Di::_()->get('Database\Cassandra\Cql');

        $prepared = new \Minds\Core\Data\Cassandra\Prepared\Custom;

        $placeholders = implode(', ', array_fill(0, count($userGuids), '?'));
        $statement = "SELECT * FROM blockchain_transactions_mainnet
             WHERE user_guid IN ({$placeholders})
                AND  amount>=?
                AND timestamp > ?
                ALLOW FILTERING";

        $offset = "";

        while (true) {
            $values = array_map(function ($userGuid) {
                return new \Cassandra\Varint($userGuid);
            }, $userGuids);
            $values[] = new \Cassandra\Varint(5);
            $values[] = new \Cassandra\Timestamp(strtotime('35 days ago'), 0);
            $prepared->query($statement, $values);

            $prepared->setOpts([
                'paging_state_token' => $offset,
                'page_size' => 500,
            ]);

            try {
                $result = $cql->request($prepared);
                if (!$result) {
                    break;
                }

                $offset = $result->pagingStateToken();
            } catch (\Exception $e) {
                var_dump($e);
                return;
            }
            foreach ($result as $row) {
                $data = json_decode($row['data'], true);

                /*if ($row['timestamp']->time() < strtotime('35 days ago')) {
                    return; // Do not sync old
                }*/

                if (!$data['sender_guid']) {
                    var_dump($row);
                }
                $wire = new \Minds\Core\Wire\Wire();
                $wire
                    ->setSender(new \Minds\Entities\User($data['sender_guid']))
                    ->setReceiver(new \Minds\Entities\User($data['receiver_guid']))
                    ->setEntity(\Minds\Entities\Factory::build($data['entity_guid']))
                    ->setAmount((string) $data['amount'])
                    ->setTimestamp((int) $row['timestamp']->time());
                yield $wire;
            }

            if ($result->isLastPage()) {
                return;
            }
        }
    }

    public function recharge()
    {
        $id = $this->getOpt('id');

        $manager = Di::_()->get('Payments\Subscriptions\Manager');
        $subscription = $manager->get($id);

        $manager->setSubscription($subscription);
        $manager->charge();
    }
    
    /**
     * Updates the annual plus subscriptions for plus
     */
    public function updatePlusSubscriptions()
    {
        /** @var Manager $manager */
        $manager = Di::_()->get('Payments\Subscriptions\Manager');

        $cql = \Minds\Core\Di\Di::_()->get('Database\Cassandra\Cql');

        $prepared = new \Minds\Core\Data\Cassandra\Prepared\Custom;

        $subscriptions = Di::_()->get('Payments\Subscriptions\Iterator');
        $subscriptions->setFrom(0)
            ->setPaymentMethod('tokens')
            ->setPlanId('wire');

        foreach ($subscriptions as $subscription) {
            if ($subscription->getInterval() !== 'yearly') {
                continue;
            }
            if ($subscription->getEntity()->getGuid() != '730071191229833224') {
                continue;
            }
            $subscription->setAmount(48000000000000000000);
            $manager->setSubscription($subscription);
            $manager->create();
        }

        /*        $amount = new \Cassandra\Decimal(48000000000000000000);
                $statement = "UPDATE subscriptions SET amount=?
                    WHERE plan_id='wire'
                        AND payment_method='tokens'
                        AND entity_guid=?";
                $values = [ $amount, new \Cassandra\Varint(730071191229833224) ];

                $prepared->query($statement, $values);
                try {
                    $result = $cql->request($prepared);
                    echo "done";
                } catch (\Exception $e) {
                    var_dump($e);
                }
         */
    }

    private function getPreviousPayments(string $userGuid): iterable
    {
        $customersManager = new \Minds\Core\Payments\Stripe\Customers\Manager();

        $customer = $customersManager->getFromUserGuid($userGuid);

        if (!$customer) {
            return;
        }

        $instance = new \Minds\Core\Payments\Stripe\Instances\ChargeInstance();

        $items = $instance->all([
            'customer' => $customer->getId()
        ]);

        foreach ($items->autoPagingIterator() as $item) {
            yield $item;
        }
    }

    public function fixFailed()
    {
        $scroll = Di::_()->get('Database\Cassandra\Cql\Scroll');
        $cql = Di::_()->get('Database\Cassandra\Cql');

        $statement = "SELECT * FROM subscriptions WHERE status='failed' ALLOW FILTERING";

        $q = new \Minds\Core\Data\Cassandra\Prepared\Custom();
        $q->query($statement, []);

        $i = 0;
        foreach ($scroll->request($q) as $row) {
            $nextBilling = $row['next_billing']->time();

            if ($nextBilling < strtotime('3 months ago')) {
                continue;
            }

            ++$i;
            $date = date('c', $nextBilling);

            // Patch
            $statement = "UPDATE subscriptions SET status='active'
                    WHERE plan_id = ? 
                    AND payment_method = ?
                    AND entity_guid = ?
                    AND user_guid = ?
                    AND subscription_id = ?";

            $values = [
                $row['plan_id'],
                $row['payment_method'],
                $row['entity_guid'],
                $row['user_guid'],
                $row['subscription_id'],
            ];

            $w = new \Minds\Core\Data\Cassandra\Prepared\Custom();
            $w->query($statement, $values);

            $cql->request($w);

            $this->out("$i - $date");
        }
    }
}
