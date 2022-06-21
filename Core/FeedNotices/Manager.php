<?php

namespace Minds\Core\FeedNotices;

use Minds\Core\FeedNotices\Notices\VerifyEmailNotice;
use Minds\Core\FeedNotices\Notices\ConnectWalletNotice;
use Minds\Core\FeedNotices\Notices\SetupChannelNotice;
use Minds\Core\FeedNotices\Notices\VerifyUniquenessNotice;
use Minds\Core\FeedNotices\Notices\BuildYourAlgorithmNotice;
use Minds\Core\FeedNotices\Notices\UpdateTagsNotice;
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
    private const NOTICES = [
        VerifyEmailNotice::class,
        BuildYourAlgorithmNotice::class,
        UpdateTagsNotice::class,
        SetupChannelNotice::class,
        VerifyUniquenessNotice::class,
        ConnectWalletNotice::class,
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

        foreach (self::NOTICES as $noticeClass) {
            $notice = (new $noticeClass())
                ->setUser($user);
            array_push($notices, $notice->export());
        }

        return $notices;
    }
}
