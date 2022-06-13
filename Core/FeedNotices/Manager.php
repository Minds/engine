<?php

namespace Minds\Core\FeedNotices;

use Minds\Core\FeedNotices\Notices\BuildYourAlgorithmNotice;
use Minds\Core\FeedNotices\Notices\UpdateTagsNotice;
use Minds\Core\FeedNotices\Notices\VerifyEmailNotice;
use Minds\Core\FeedNotices\Notices\EnablePushNotificationsNotice;
use Minds\Entities\User;

/**
 * Manager for FeedNotices - can be used to get notices.
 */
class Manager
{
    /**
     * The priority that notices will show in is determined by
     * the order of Notices in this array.
     */
    const NOTICES = [
        VerifyEmailNotice::class,
        BuildYourAlgorithmNotice::class,
        UpdateTagsNotice::class,
        EnablePushNotificationsNotice::class,
    ];

    /**
     * Get exported notices that can be consumed by the front-end.
     * @param User $user - user to get notices for.
     * @return array notices.
     */
    public function getNotices(User $user): array
    {
        $notices = [];

        for ($i = 0; $i < count(self::NOTICES); $i++) {
            $notice = (new (self::NOTICES[$i])())
                ->setUser($user);
            array_push($notices, $notice->export());
        }

        return $notices;
    }
}
