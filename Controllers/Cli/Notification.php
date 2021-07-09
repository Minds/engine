<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Events\Dispatcher;
use Minds\Interfaces;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\EmailDigests\EmailDigestMarker;
use Minds\Core\Notifications\EmailDigests\EmailDigestOpts;

class Notification extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        switch ($command) {
            case 'send':
                $this->out('Send a notification');
                $this->out('--namespace=<type> Notification namespace');
                $this->out('--to=<user guid> User to send notification to');
                $this->out('--from=<entity guid> Entity notification is from (defaults to system user)');
                $this->out('--view=<view> Notification view');
                $this->out('--params=<params> JSON payload data');
            // no break
            default:
                $this->out('Syntax usage: cli notification <cmd>');
                $this->displayCommandHelp();
        }
    }

    public function exec()
    {
        $this->help();
    }

    public function push()
    {
        $urn = $this->getOpt('urn');
        ;
        $notificationsManager = Di::_()->get('Notifications\Manager');
        $notification = $notificationsManager->getByUrn($urn);

        $pushManager = Di::_()->get('Notifications\Push\Manager');
        $pushManager->sendPushNotification($notification);
    }

    /**
     * Sends digest emails
     * NOTE: this uses the last period, so last month.
     */
    public function emailDigests()
    {
        $frequency = $this->getOpt('frequency');

        $emailDigestsManager = Di::_()->get('Notifications\EmailDigests\Manager');

        switch ($frequency) {
            case 'daily':
                $timestamp = strtotime('midnight yesterday');
                break;
            case 'weekly':
                $timestamp = strtotime('midnight monday last week');
                break;
            case 'periodically':
            case EmailDigestMarker::FREQUENCY_PERIODICALLY:
            default:
                $timestamp = strtotime('midnight first day of last month');
        }

        $opts = new EmailDigestOpts();
        $opts->setFrequency($frequency)
            ->setTimestamp($timestamp);

        foreach ($emailDigestsManager->sendBulk($opts) as $item) {
            $this->out($item->getEntity()->getToGuid());
        }
    }
}
