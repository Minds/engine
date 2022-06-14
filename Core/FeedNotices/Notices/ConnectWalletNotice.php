<?php

namespace Minds\Core\FeedNotices\Notices;

use Minds\Entities\User;

/**
 * Feed notice to prompt a user to connect their wallet.
 */
class ConnectWalletNotice extends AbstractNotice
{
    // location of notice in feed.
    private const LOCATION = 'inline';

    // notice key / identifier.
    private const KEY = 'connect-wallet';

    public function __construct(
        private ?VerifyUniquenessNotice $verifyUniquenessNotice = null
    ) {
        $this->verifyUniquenessNotice ??= new VerifyUniquenessNotice();
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
     * previously connected their ETH wallet and meets pre-requisites.
     * @param User $user - user to check for.
     * @return boolean - true if notice should show.
     */
    public function shouldShow(User $user): bool
    {
        return !$user->getEthWallet() &&
            $this->verifyUniquenessNotice->meetsPrerequisites($user);
    }
}
