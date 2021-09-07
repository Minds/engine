<?php
/**
 * Minds EmailDigests Notifications Manager.
 */

namespace Minds\Core\Notifications\EmailDigests;

use Minds\Common\Repository\IterableEntity;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\Notification;
use Minds\Core\Email;
use Minds\Core\Email\EmailSubscription;
use Minds\Core\Email\V2\Campaigns\Recurring\UnreadNotifications\UnreadNotifications;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Features;
use Minds\Entities\User;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var Email\Repository */
    protected $emailRepository;

    /** @var UnreadNotifications */
    protected $unreadNotificationsEmail;

    /** @var Features\Manager */
    protected $featuresManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct(
        Repository $repository = null,
        Email\Repository $emailRepository = null,
        UnreadNotifications $unreadNotificationsEmail = null,
        Features\Manager $featuresManager = null,
        EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->repository = $repository ?? new Repository();
        $this->emailRepository = $emailRepository ?? Di::_()->get('Email\Repository');
        $this->unreadNotificationsEmail = $unreadNotificationsEmail ?? new UnreadNotifications();
        $this->featuresManager = $featuresManager ?? Di::_()->get('Features\Manager');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param EmailDigestMarker $marker
     * @return void
     */
    public function sendEmail(EmailDigestMarker $marker): void
    {
        /** @var User */
        $toUser = $this->entitiesBuilder->single($marker->getToGuid());

        // Only if the user allows the feature flag, should we send the email
        if (!$this->featuresManager->setUser($toUser)->has('notifications-v3')) {
            return;
        }

        $this->unreadNotificationsEmail
            ->setUser($toUser)
            ->send();
    }

    /**
     * Send all emails for frequency and time period
     * @param EmailDigestOpts $opts
     * @return iterable<IterableEntity>
     */
    public function sendBulk(EmailDigestOpts $opts): iterable
    {
        foreach ($this->getList($opts) as $iterable) {
            $this->sendEmail($iterable->getEntity());
            yield $iterable;
        }
    }

    /**
     * @param EmailDigestOpts $opts
     * @return iterable<IterableEntity>
     */
    public function getList(EmailDigestOpts $opts): iterable
    {
        return $this->repository->getList($opts);
    }

    /**
     * Adds a marker to the queue, alongside which frequency the user has selected
     * @param Notification $notification
     * @return bool
     */
    public function addToQueue(Notification $notification): bool
    {
        $marker = new EmailDigestMarker();

        $result = $this->emailRepository->getList([
            'campaigns' => [ 'when' ],
            'topics' => [ 'unread_notifications' ],
            'user_guid' => $notification->getToGuid(),
        ]);

        if (!isset($result['data']) || !$result['data'][0]) {
            $emailSubscription = new EmailSubscription([
                'campaign' => 'when',
                'topic' => 'unread_notification',
                'user_guid' => $notification->getToGuid(),
                'value' => 'weekly'
            ]);
        } else {
            $emailSubscription = $result['data'][0];
        }
        
        // Due to legacy loose-typing, we need to remap some values here
        switch ($emailSubscription->getValue()) {
            case 'daily':
                $frequency = EmailDigestMarker::FREQUENCY_DAILY;
                $timestamp = $notification->getCreatedTimestamp(); // Same day
                break;
            case 'periodically':
                $frequency = EmailDigestMarker::FREQUENCY_PERIODICALLY;
                $timestamp = strtotime('midnight first day of this month', $notification->getCreatedTimestamp()); // First day of month
                break;
            case 'weekly':
            default:
                $frequency = EmailDigestMarker::FREQUENCY_WEEKLY;
                $timestamp = strtotime('midnight monday this week', $notification->getCreatedTimestamp()); // First day of week
                break;
        }

        $marker->setToGuid($notification->getToGuid())
            ->setFrequency($frequency)
            ->setTimestamp($timestamp);

        return $this->repository->add($marker);
    }
}
