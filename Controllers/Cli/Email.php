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
use Minds\Core\Email\V2\Campaigns\Recurring\WelcomeComplete\WelcomeComplete;
use Minds\Core\Email\V2\Campaigns\Recurring\WelcomeIncomplete\WelcomeIncomplete;
use Minds\Core\Email\V2\Campaigns\Recurring\WeMissYou\WeMissYou;
use Minds\Core\Email\Campaigns\Recurring\WirePromotions;
use Minds\Core\Email\V2\Delegates\ConfirmationSender;
use Minds\Core\Reports;
use Minds\Core\Blockchain\Purchase\Delegates\IssuedTokenEmail;
use Minds\Core\Blockchain\Purchase\Delegates\NewPurchaseEmail;
use Minds\Core\Blockchain\Purchase\Purchase;

use Minds\Core\Suggestions\Manager;
use Minds\Core\Analytics\Timestamps;
use Minds\Core\Di\Di;

class Email extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        switch ($command) {
            case 'exec':
                $this->out(file_get_contents(dirname(__FILE__).'/Help/Email/exec.txt'));
                break;
            case 'testBoostComplete':
                $this->out(file_get_contents(dirname(__FILE__).'/Help/Email/testBoostComplete.txt'));
                break;
            case 'testWeMissYou':
                $this->out(file_get_contents(dirname(__FILE__).'/Help/Email/testWeMissYou.txt'));
                break;
            case 'testWelcomeComplete':
                $this->out(file_get_contents(dirname(__FILE__).'/Help/Email/testWelcomeComplete.txt'));
                break;
            case 'testWelcomeIncomplete':
                $this->out(file_get_contents(dirname(__FILE__).'/Help/Email/testWelcomeIncomplete.txt'));
                break;
            case 'testWire':
                $this->out(file_get_contents(dirname(__FILE__).'/Help/Email/testWire.txt'));
                break;
            case 'testWirePromotion':
                $this->out(file_get_contents(dirname(__FILE__).'/Help/Email/testWirePromotion.txt'));
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
     */
    public function exec()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $dry = $this->getOpt('dry-run') ?: false;

        $offset = $this->getOpt('offset') ?: '';
        $campaign = Campaigns\Factory::build($this->getOpt('campaign'));

        if ($dry) {
            $iterator = [
                (new User($dry))
            ];
        } else {
            $iterator = new EmailSubscribersIterator();
            $iterator->setCampaign($campaign->getCampaign())
                ->setTopic($campaign->getTopic())
                ->setValue(true)
                ->setOffset($offset);
        }

        $i = 0;
        foreach ($iterator as $user) {
            if (!$user instanceof User || !method_exists($user, 'getEmail')) {
                continue;
            }
            if ($user->bounced && !$dry) {
                $this->out("[$i]: $user->guid ($iterator->offset) bounced");
                continue;
            }

            ++$i;

            $campaign = clone $campaign;
            $campaign->setUser($user);
            $campaign->send();

            $this->out("[$i]: $user->guid ($iterator->offset) sent");
        }

        $this->out('Done.');
    }


    //

    public function topPosts()
    {
        $this->out('Top posts');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $period = $this->getOpt('period');
        $offset = '';

        if (!$period || $period !== 'periodically' && $period !== 'daily' && $period !== 'weekly') {
            throw new CliException('You must set a correct period (periodically, daily or weekly)');
        }

        $batch = Core\Email\Batches\Factory::build('activity');

        $batch->setPeriod($period)
            ->setOffset($offset)
            ->run();
        $this->out('done');
    }

    public function RetentionTips()
    {
        $this->out('Retention emails');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $period = $this->getOpt('period');
        $offset = '';

        $batch = Core\Email\Batches\Factory::build('RetentionTips');

        $batch->setPeriod($period)
            ->setOffset($offset)
            ->run();
        $this->out('done');
    }

    public function unreadNotifications()
    {
        $offset = $this->getOpt('offset') ?: '';

        $batch = Core\Email\Batches\Factory::build('notifications');
        $batch->setOffset($offset)
            ->run();
    }

    public function testWeMissYou()
    {
        $userguid = $this->getOpt('guid');
        $output = $this->getOpt('output');
        $send = $this->getOpt('send');
        $user = new User($userguid);

        if (!$user->guid) {
            $this->out('User not found');
            exit;
        }

        $manager = Di::_()->get('Suggestions\Manager');
        $manager->setUser($user);
        $suggestions = $manager->getList();

        $campaign = (new WeMissYou())
            ->setUser($user)
            ->setSuggestions($suggestions);

        $message = $campaign->build();

        if ($send) {
            Core\Events\Dispatcher::trigger('user_state_change', 'cold', [ 'user_guid' => $userguid ]);
        }

        if ($output) {
            file_put_contents($output, $message->buildHtml());
        } else {
            $this->out($message->buildHtml());
        }
    }

    public function testWelcomeSender()
    {
        $userguid = $this->getOpt('guid');
        $output = $this->getOpt('output');
        $send = $this->getOpt('send');
        $user = new User($userguid);

        if (!$user->guid) {
            $this->out('User not found');
            exit;
        }
    
        if ($send) {
            Core\Events\Dispatcher::trigger('welcome_email', 'all', [ 'user_guid' => $userguid ]);
        }
    }

    public function testWelcomeComplete()
    {
        $userguid = $this->getOpt('guid');
        $output = $this->getOpt('output');
        $send = $this->getOpt('send');
        $user = new User($userguid);

        if (!$user->guid) {
            $this->out('User not found');
            exit;
        }

        $manager = new Manager();
        $manager2 = new Manager();

        $manager->setUser($user);
        $suggestions = $manager->getList();

        $campaign = (new WelcomeComplete())
            ->setUser($user)
            ->setSuggestions($suggestions);

        $message = $campaign->build();

        if ($send) {
            $campaign->send();
        }

        if ($output) {
            file_put_contents($output, $message->buildHtml());
        } else {
            $this->out($message->buildHtml());
        }
    }

    public function testWirePromotion()
    {
        $userguid = $this->getOpt('guid');
        $output = $this->getOpt('output');
        $send = $this->getOpt('send');
        $user = new User($userguid);

        if (!$user->guid) {
            $this->out('User not found');
            exit;
        }

        $campaign = (new WirePromotion())
            ->setUser($user);

        $message = $campaign->build();

        if ($send) {
            $campaign->send();
        }
    }

    public function testWelcomeIncomplete()
    {
        $userguid = $this->getOpt('guid');
        $output = $this->getOpt('output');
        $send = $this->getOpt('send');
        $user = new User($userguid);

        if (!$user->guid) {
            $this->out('User not found');
            exit;
        }

        $campaign = (new WelcomeIncomplete())
            ->setUser($user);

        $message = $campaign->build();

        if ($send) {
            $campaign->send();
        }

        if ($output) {
            file_put_contents($output, $message->buildHtml());
        } else {
            $this->out($message->buildHtml());
        }
    }

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

        $boost = $manager->get("urn:boost:{$boostType}:{$entityGuid}", [ 'hydrate' => true ]);

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

    public function testModerationBanned()
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

        $banDelegate = new Reports\Verdict\Delegates\EmailDelegate();
        $report = new Reports\Report();
        $report->setEntityUrn($entityUrn);
        $report->setReasonCode($reasonCode);

        $banDelegate->onBan($report);
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

    public function sync_sendgrid_lists(): void
    {
        $sendGridManager = Di::_()->get('SendGrid\Manager');
        $sendGridManager->syncContactLists();
    }
}
