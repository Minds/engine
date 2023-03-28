<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Entities\User;
use Minds\Core\Email\EmailSubscribersIterator;
use Minds\Core\Email\V2\Campaigns;
use Minds\Core\Email\V2\Campaigns\Recurring\BoostComplete\BoostComplete;
use Minds\Core\Email\V2\Campaigns\Recurring\WireReceived\WireReceived;
use Minds\Core\Email\V2\Campaigns\Recurring\WireSent\WireSent;
use Minds\Core\Email\V2\Delegates\ConfirmationSender;
use Minds\Core\Email\V2\Delegates\DigestSender;
use Minds\Core\Reports;
use Minds\Core\Blockchain\Purchase\Delegates\IssuedTokenEmail;
use Minds\Core\Blockchain\Purchase\Delegates\NewPurchaseEmail;
use Minds\Core\Blockchain\Purchase\Purchase;
use Minds\Core\Supermind;
use Minds\Core\Email\V2\Campaigns\Recurring\Supermind\Supermind as SupermindEmail;

use Minds\Core\Suggestions\Manager;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Campaigns\Recurring\SupermindBulkIncentive\SupermindBulkIncentive;
use Minds\Core\Email\V2\SendLists;

class Email extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        switch ($command) {
            case 'exec':
                $this->out(file_get_contents(dirname(__FILE__) . '/Help/Email/exec.txt'));
                break;
            case 'testBoostComplete':
                $this->out(file_get_contents(dirname(__FILE__) . '/Help/Email/testBoostComplete.txt'));
                break;
            case 'testWire':
                $this->out(file_get_contents(dirname(__FILE__) . '/Help/Email/testWire.txt'));
                break;
            case 'testWirePromotion':
                $this->out(file_get_contents(dirname(__FILE__) . '/Help/Email/testWirePromotion.txt'));
                break;
            default:
                $this->out('Utilities for testing emails and sending them manually');
                $this->out('try `cli Email {command} --help');
                $this->displayCommandHelp();
        }
    }

    /**
     * TODO: Move this to Core
     * How to run? Eg:
     * php cli.php Email \
     *  --campaign="Marketing\\Languages2020_06_18\\Languages2020_06_18"
     *  --send-list="GenericSendList"
     */
    public function exec()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $dry = $this->getOpt('dry-run') ?: false;

        $offset = $this->getOpt('offset') ?: '';
        $campaign = Campaigns\Factory::build($this->getOpt('campaign'));
        $sendList = SendLists\Factory::build($this->getOpt('send-list'));
        $sendList->setCampaign($campaign);
        $sendList->setOffset($offset);
        $sendList->setCliOpts($this->getAllOpts());

        $i = 0;
        foreach ($sendList->getList() as $user) {
            if (!$user instanceof User || !method_exists($user, 'getEmail')) {
                continue;
            }
            if ($user->bounced && !$dry) {
                $this->out("[$i]: $user->guid ($sendList->offset) bounced");
                continue;
            }

            ++$i;

            $campaign = clone $campaign;

            if ($campaign instanceof SupermindBulkIncentive) {
                $campaign = $campaign
                    ->withActivityGuid($this->getOpt('activity-guid'))
                    ->withReplyType((int) $this->getOpt('reply-type'));
            }

            $campaign->setUser($user);

            if (!$dry) {
                $campaign->send();
            }

            $this->out("[$i]: $user->guid ($sendList->offset) sent");
        }

        $this->out('Done.');
    }


    //


    public function testWire()
    {
        $output = $this->getOpt('output');
        $entityGuid = $this->getOpt('guid');
        $senderGuid = $this->getOpt('sender');
        $timestamp = $this->getOpt('timestamp');
        $variant = $this->getOpt('variant');

        $send = $this->getOpt('send');

        $repository = Di::_()->get('Wire\Repository');

        if (!$entityGuid) {
            $this->out('--guid=wire guid required');
            exit;
        }

        if (!$senderGuid) {
            $this->out('--sender=guid required');
            exit;
        }

        if (!$timestamp) {
            $this->out('--timestamp=timestamp required');
            exit;
        }

        $wireResults = $repository->getList([
            'entity_guid' => $entityGuid,
            'sender_guid' => $senderGuid,
            'timestamp' => [
                'gte' => $timestamp,
                'lte' => $timestamp,
            ],
        ]);

        if (!$wireResults || count($wireResults['wires']) === 0) {
            $this->out('Wire not found');
            exit;
        }
        $wire = $wireResults['wires'][0];

        if ($variant === 'sent') {
            $campaign = (new WireSent());
        } elseif ($variant === 'received') {
            $campaign = (new WireReceived());
        } else {
            $this->out('--variant must be sent or received');
            return;
        }

        $campaign
            ->setUser($wire->getReceiver())
            ->setWire($wire);

        $message = $campaign->build();

        if ($send) {
            $campaign->send();
            $this->out('sent');
        }

        if ($output) {
            file_put_contents($output, $message->buildHtml());
        } else {
            $this->out($message->buildHtml());
        }
    }

    public function testBoostComplete()
    {
        $output = $this->getOpt('output');
        $entityGuid = $this->getOpt('guid');
        $boostType = $this->getOpt('type');
        $send = $this->getOpt('send');

        $manager = Di::_()->get('Boost\Network\Manager');

        if (!$entityGuid) {
            $this->out('--guid=boost guid required');
            exit;
        }

        if (!$boostType) {
            $this->out('--type=boost type required');
            exit;
        }

        $boost = $manager->get("urn:boost:{$boostType}:{$entityGuid}", ['hydrate' => true]);

        if (!$boost) {
            $this->out('Boost not found');
            exit;
        }

        $campaign = (new BoostComplete())
            ->setUser($boost->getOwner())
            ->setBoost($boost->export());

        $message = $campaign->build();

        if ($send) {
            Core\Events\Dispatcher::trigger('boost:completed', 'boost', ['boost' => $boost]);
        }

        if ($output) {
            file_put_contents($output, $message->buildHtml());
        } else {
            $this->out($message->buildHtml());
        }
    }

    public function testConfirmationEmail()
    {
        $userGuid = $this->getOpt('guid');
        // $output = $this->getOpt('output');
        // $send = $this->getOpt('send');

        $user = new User($userGuid);
        $sender = new ConfirmationSender();
        $sender->send($user);

        $this->out('sent');
    }

    /**
     * Example usage:
     *  php cli.php Email testModerationBanned
     * --entityUrn=urn:user:12345
     * --reasonCode=1
     * --subReasonCode=4
     * --output=/var/www/Minds/engine/mod_banned.html
     *
     * reasonCode opt(s) will be used only if
     * your user doesn't have a ban_reason already
     */
    public function testModerationBanned()
    {
        $entityUrn = $this->getOpt('entityUrn');
        $output = $this->getOpt('output');
        $reasonCode = $this->getOpt('reasonCode');
        $subReasonCode = $this->getOpt('subReasonCode');

        if (!$entityUrn) {
            return $this->out('entityUrn must be supplied');
        }

        $banDelegate = new Reports\Verdict\Delegates\EmailDelegate();
        $report = new Reports\Report();
        $report->setEntityUrn($entityUrn);

        if ($reasonCode) {
            $report->setReasonCode($reasonCode);
        }
        if ($subReasonCode) {
            $report->setSubReasonCode($subReasonCode);
        }

        $banDelegate->onBan($report);

        if ($output) {
            $message = $banDelegate->getCampaign()->getMessage();
            file_put_contents($output, $message->buildHtml());
        }
    }

    public function testModerationStrike()
    {
        $entityUrn = $this->getOpt('entityUrn');

        if (!$entityUrn) {
            return $this->out('entityUrn must be supplied');
        }

        $userGuid = $this->getOpt('guid');
        $user = new User($userGuid);

        if (!$userGuid) {
            return $this->out('userGuid must be supplied');
        }

        // Use 8 for strike
        $reasonCode = $this->getOpt('reasonCode');

        $strikeDelegate = new Reports\Strikes\Delegates\EmailDelegate();

        $report = new Reports\Report();
        $report->setEntityUrn($entityUrn);
        $report->setReasonCode($reasonCode);
        $strike = new Reports\Strikes\Strike();
        $strike->setReport($report);

        $strikeDelegate->onStrike($strike);
    }

    /**
     * Example usage:
     *  php cli.php Email testHackedAccount
     * --entityUrn=urn:user:12345
     * --output=/var/www/Minds/engine/mod_banned.html
     */
    public function testHackedAccount()
    {
        $entityUrn = $this->getOpt('entityUrn');
        $output = $this->getOpt('output');


        if (!$entityUrn) {
            return $this->out('entityUrn must be supplied');
        }

        $report = new Reports\Report();
        $report->setEntityUrn($entityUrn);
        $report->setReasonCode(17);
        $report->setSubReasonCode(1);

        $emailDelegate = new Reports\Verdict\Delegates\EmailDelegate();

        $emailDelegate->onHack($report);

        if ($output) {
            $message = $emailDelegate->getCampaign()->getMessage();
            file_put_contents($output, $message->buildHtml());
        }
    }

    public function testTokenPurchase()
    {
        $issued = $this->getOpt('issued');
        $amount = $this->getOpt('amount') ?: 10 ** 18;


        $userGuid = $this->getOpt('userGuid');
        $user = new User($userGuid);

        $purchase = new Purchase();
        $purchase->setUserGuid($userGuid)
            ->setRequestedAmount($amount);

        if ($issued) {
            $delegate = new IssuedTokenEmail();
        } else {
            $delegate = new NewPurchaseEmail();
        }

        $delegate->send($purchase);
    }

    public function testDigest()
    {
        $userGuid = $this->getOpt('userGuid');
        $user = new User($userGuid);

        $digest = new DigestSender();
        $digest->send($user);

        $this->out('Sent');
    }

    public function testPlusTrial()
    {
        $userGuid = $this->getOpt('userGuid');
        $user = new User($userGuid);

        $subscription = new Core\Payments\Subscriptions\Subscription();
        $subscription
            ->setTrialDays(7)
            ->setUser($user)
            ->setEntity(new User(730071191229833224))
            ->setNextBilling(strtotime('+7 days'));

        $emailDelegate = new Core\Payments\Subscriptions\Delegates\EmailDelegate();
        $emailDelegate->onCreate($subscription);

        $this->out('End.');
    }

    /**
     * Example usage:
     * php cli.php Email testSupermind --topic='supermind_request_sent' --output=/var/www/Minds/engine/supermind.html --senderGuid='1215744293826727938' --receiverGuid='1107439332647505934' --activityGuid='1416522245517348865' --paymentMethod=1 --paymentAmount=12.345
     *
     * Payment method can be 0 (cash) or 1 (off-chain tokens)
     * See the Supermind emailer for topics
     */
    public function testSupermind()
    {
        $output = $this->getOpt('output');
        $topic = $this->getOpt('topic');
        $senderGuid = $this->getOpt('senderGuid');
        $receiverGuid = $this->getOpt('receiverGuid');
        $activityGuid = $this->getOpt('activityGuid');
        $paymentMethod = $this->getOpt('paymentMethod');
        $paymentAmount = $this->getOpt('paymentAmount');

        $supermindRequest = new Supermind\Models\SupermindRequest();

        $supermindRequest->setActivityGuid($activityGuid)
        ->setSenderGuid($senderGuid)
        ->setReceiverGuid($receiverGuid)
        ->setPaymentMethod($paymentMethod)
        ->setPaymentAmount($paymentAmount);

        $supermindEmailer = new SupermindEmail();
        $supermindEmailer->setTopic($topic)
            ->setSupermindRequest($supermindRequest);

        if ($output) {
            $message = $supermindEmailer->build();
            file_put_contents($output, $message->buildHtml());
        }
    }

    public function sync_sendgrid_lists(): void
    {
        Di::_()->get('Config')->set('min_log_level', 'INFO');

        $sendGridManager = Di::_()->get('SendGrid\Manager');
        $sendGridManager->syncContactLists();
    }

    public function sync_marketing_attributes()
    {
        ini_set('memory_limit', '2G');
        Di::_()->get('Config')->set('min_log_level', 'INFO');

        $mautic = new Core\Email\Mautic\MarketingAttributes\Manager();
        $mautic->sync();
    }

    public function sync_mautic()
    {
        Di::_()->get('Config')->set('min_log_level', 'INFO');

        $fromTs = null;
        $offset = $this->getOpt('offset') ?: 0;

        if ($fromDate = $this->getOpt('from-timestamp')) {
            $fromTs = strtotime($fromDate);
        }

        if ($hoursAgo = $this->getOpt('hours-ago')) {
            $fromTs = strtotime("$hoursAgo hours ago");
        }

        $mautic = Di::_()->get(Core\Email\Mautic\Manager::class);
        $mautic->sync(fromTs: $fromTs, offset: $offset);
    }
}
