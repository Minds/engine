<?php

namespace Minds\Controllers\Cli;

use Cassandra\Bigint;
use Minds\Cli;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\EmailDigests\EmailDigestMarker;
use Minds\Core\Notifications\EmailDigests\EmailDigestOpts;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\System\Manager;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Core\Notifications\Push\System\Targets\SystemPushNotificationTargetsList;
use Minds\Core\Notifications\Push\UndeliverableException;
use Minds\Interfaces;

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

    /**
     * Send a push notification to all devices
     * @throws UndeliverableException
     */
    public function sendPush()
    {
        $dryRun = $this->getOpt('dry-run') ?? false;
        $testUserGuid = $this->getOpt('user-guid');
        $scroll = Di::_()->get('Database\Cassandra\Cql\Scroll');

        $statement = "SELECT * FROM push_notifications_device_subscriptions";
        $values = [];

        if ($testUserGuid) {
            $statement .= " WHERE user_guid = ?";
            $values[] = new Bigint($testUserGuid);
        }

        $prepared = new Custom();
        $prepared->query($statement, $values);

        $i = 0;
        $pushManager = new Manager();
        foreach ($scroll->request($prepared) as $row) {
            $userGuid = $row['user_guid'];

            $deviceSubscription = new DeviceSubscription();
            $deviceSubscription->setUserGuid((string) $row['user_guid'])
                ->setToken($row['device_token'])
                ->setService($row['service']);

            $this->out("$i: $userGuid");

            if (!$dryRun) {
                $pushNotification = new CustomPushNotification();
                $pushNotification
                    ->setDeviceSubscription($deviceSubscription)
                    ->setTitle('💡 Watch Minds on Joe Rogan Experience 💡')
                    ->setBody('Joe Rogan interviews Minds CEO Bill Ottman and Daryl Davis')
                    ->setUri('https://www.minds.com/newsfeed/1350517575166988298');

                $pushManager->sendNotification($pushNotification, NotificationTypes::GROUPING_TYPE_COMMUNITY_UPDATES);
                //send
            }
        }
    }

    /**
     * Send push notification(s) for a top unseen post.
     * @throws UndeliverableException - when notification is undeliverable.
     * @throws ServerErrorException - when a server error occurs such as when unable to get
     * an unseen top post.
     * @example usage:
     * - php cli.php Notification sendTopPostPush --user_guid=1285556899399340038 --target_list=AllAndroidAppDevices
     */
    public function sendTopPostPush()
    {
        $singleUserGuid = $this->getOpt('user_guid') ?? null;
        $targetListClassName = $this->getOpt('target_list') ?? 'AllDevices';

        /** @var TopPost\Manager */
        $topPostPushManager = Di::_()->get('Notifications\Push\TopPost\Manager');

        $notificationTargetHandler = SystemPushNotificationTargetsList::getTargetHandlerFromClassName(
            $targetListClassName
        );

        $deviceSubscriptions = $notificationTargetHandler->getList();

        foreach ($deviceSubscriptions as $deviceSubscription) {
            // skip over if we're only sending to a single user.
            if ($singleUserGuid && $singleUserGuid !== (string) $deviceSubscription->getUserGuid()) {
                continue;
            }
            try {
                $topPostPushManager->sendSingle($deviceSubscription);
                $this->out('[TopPost CLI] Success: dispatched to ' . $deviceSubscription->getUserGuid());
            } catch (\Exception $e) {
                $this->out(
                    '[TopPost CLI] Error: user_guid: ' .
                    $deviceSubscription->getUserGuid() .
                    ', message: ' .
                    $e->getMessage()
                );
            }
        }

        $this->out('[TopPost CLI] Done.');
    }
}
