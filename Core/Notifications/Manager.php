<?php
namespace Minds\Core\Notifications;

use Minds\Entities\User;
use Minds\Helpers\Counters;

class Manager
{
    /** @var Repository */
    protected $repository;

    public function __construct(Repository $repository = null)
    {
        $this->repository = $repository ?? new Repository();
    }

    /**
     * @param User $user
     * @return int
     */
    public function getUnreadCount(User $user): int
    {
        return Counters::get($user, 'notifications:v3:count', false);
    }

    /**
     * @param User $user
     * @param int $incrementBy
     * @return void
     */
    public function incrementCount(User $user, $incrementBy = 1): void
    {
        Counters::increment($user, 'notifications:v3:count', $incrementBy);
    }

    /**
     * @param User $user
     * @return void
     */
    public function resetCount(User $user): void
    {
        Counters::clear($user, 'notifications:v3:count');
    }

    /**
     * @param string $uuid
     * @return Notification
     */
    public function get(string $uuid): ?Notification
    {
        return null;
    }

    /**
     * @param NotificationsListOpts $opts
     * @return iterable
     */
    public function getList(NotificationsListOpts $opts): iterable
    {
        // Merge logic
        /** @var Notification[] */
        $mergeKeysToNotification = [];

        $count = 0;
        foreach ($this->repository->getList($opts) as $tuple) {
            /** @var Notification */
            $notification = $tuple[0];

            if ($opts->getMerge()) {
                // Was there a groupable notification above?
                // We check below with a mergedKey and combine all that match

                $mergeKey = $notification->getMergeKey();

                if ($mergeableWith = $mergeKeysToNotification[$mergeKey]) {

                    // First, check for duplication, we don't want 'sillysealion and sillysealion' vote up...
                    if ($mergeableWith->getFromGuid() === $notification->getFromGuid()) {
                        continue;
                    }

                    $mergedGuids = $notification->getMergedFromGuids();
                    $mergedGuids[] = (string) $notification->getFromGuid();
                    $mergeableWith->setMergedFromGuids($mergedGuids);
                    $mergeableWith->setMergedCount($mergeableWith->getMergedCount() + 1);
                    continue;
                } else {
                    $mergeKeysToNotification[$mergeKey] = $notification;
                }
            }

            yield $tuple;

            if (++$count >= $opts->getLimit()) {
                break;
            }
        }
    }

    /**
     * Return a single notification
     * @param string $urn
     * @return Notification
     */
    public function getByUrn(string $urn): ?Notification
    {
        return $this->repository->get($urn);
    }

    /**
     * @param Notification $notification
     * @return bool
     */
    public function add(Notification $notification): bool
    {
        $success = $this->repository->add($notification);

        if (!$success) {
            return false;
        }

        // Increment the counter
        $this->incrementCount($notification->getTo());

        // TODO: Send a push notification

        return true;
    }

    /**
     * @param Notification $notification
     * @return bool
     */
    public function delete(Notification $notification): bool
    {
        $success = $this->repository->delete($notification);

        if (!$success) {
            return false;
        }

        return true;
    }

    /**
     * @param Notification
     * @return bool
     */
    public function markAsRead(Notification $notification, User $user): bool
    {
        if ($notification->getToGuid() !== $user->getGuid()) {
            throw new \Exception('Can not edit a notification you dont own');
        }

        if ($notification->getReadTimestamp() > $notification->getCreatedTimestamp()) {
            return true; // Already marked read
        }

        $notification->setReadTimestamp(time());

        return $this->repository->update($notification, [ 'read_timestamp' ]);
    }
}
