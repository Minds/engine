<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Notifications\Push\Settings\Manager as PushSettingsManager;

/**
 * Feed notice to prompt user to enable push notifications.
 */
class EnablePushNotificationsNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'inline';

    // notice key / identifier.
    private const KEY = 'enable-push-notifications';

    /**
     * Constructor.
     * @param ?PushSettingsManager $pushSettingsManager - manager for push settings.
     */
    public function __construct(
        private ?PushSettingsManager $pushSettingsManager = null
    ) {
        $this->pushSettingsManager ??= Di::_()->get('Notifications\Push\Settings\Manager');
    }

    /**
     * Get location of notice in feed.
     * @return string location of notice in feed.
     */
    public function getLocation(): string
    {
        return self::LOCATION;
    }

    /**
     * Get notice key (identifier for notice).
     * @return string notice key.
     */
    public function getKey(): string
    {
        return self::KEY;
    }

    /**
     * Whether notice should show in feed, based on whether user has
     * all push notifications enabled.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return !$this->pushSettingsManager->hasEnabledAll(
            $user->getGuid()
        );
    }
}
