<?php

namespace Minds\Core\FeedNotices;

use Minds\Core\Di\Di;
use Minds\Core\FeedNotices\Notices\BoostChannelNotice;
use Minds\Core\FeedNotices\Notices\BuildYourAlgorithmNotice;
use Minds\Core\FeedNotices\Notices\ConnectWalletNotice;
use Minds\Core\FeedNotices\Notices\EnablePushNotificationsNotice;
use Minds\Core\FeedNotices\Notices\InAppVerifyUniquenessNotice;
use Minds\Core\FeedNotices\Notices\PlusUpgradeNotice;
use Minds\Core\FeedNotices\Notices\SetupChannelNotice;
use Minds\Core\FeedNotices\Notices\SupermindPendingNotice;
use Minds\Core\FeedNotices\Notices\UpdateTagsNotice;
use Minds\Core\FeedNotices\Notices\VerifyEmailNotice;
use Minds\Core\FeedNotices\Notices\VerifyUniquenessNotice;
use Minds\Core\Log\Logger;
use Minds\Entities\User;

/**
 * Manager for FeedNotices - can be used to get notices.
 */
class Manager
{
    /**
     * Constructor.
     * @param Logger|null - logger class.
     */
    public function __construct(private ?Logger $logger = null)
    {
        $this->logger ??= Di::_()->get('Logger');
    }

    // Priority notices, to be shown first, in order specified by array.
    private const PRIORITY_NOTICES = [
        VerifyEmailNotice::class,
        SupermindPendingNotice::class,
        InAppVerifyUniquenessNotice::class,
    ];

    // Non-priority notices - to be shown after priority notices - should be shuffled.
    private const NON_PRIORITY_NOTICES = [
        BuildYourAlgorithmNotice::class,
        UpdateTagsNotice::class,
        SetupChannelNotice::class,
        VerifyUniquenessNotice::class,
        ConnectWalletNotice::class,
        EnablePushNotificationsNotice::class,
        BoostChannelNotice::class,
        PlusUpgradeNotice::class
    ];

    /**
     * Get exported notices that can be consumed by the front-end.
     * @param User $user - user to get notices for.
     * @return array notices.
     */
    public function getNotices(User $user): array
    {
        $noticeExports = [];
        $noticeClasses = $this->getSortedNoticeClasses();

        foreach ($noticeClasses as $noticeClass) {
            try {
                $notice = (new $noticeClass())
                    ->setUser($user);
                array_push($noticeExports, $notice->export());
            } catch (\Exception $e) {
                // log error and skip over this notice.
                $this->logger->error($e);
            }
        }

        return $noticeExports;
    }

    /**
     * Get sorted notice classes where priority notices are first by index,
     * followed by shuffled non-priority notices.
     * @return array sorted notice classes.
     */
    private function getSortedNoticeClasses(): array
    {
        return [
            ...self::PRIORITY_NOTICES,
            ...$this->shuffleNotices(self::NON_PRIORITY_NOTICES)
        ];
    }

    /**
     * Return shuffle notices.
     * @param array $notices - notices to shuffle.
     * @return array shuffled notices.
     */
    private function shuffleNotices(array $notices): array
    {
        shuffle($notices);
        return $notices;
    }
}
