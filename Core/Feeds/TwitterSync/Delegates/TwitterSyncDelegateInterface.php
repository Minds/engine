<?php
namespace Minds\Core\Feeds\TwitterSync\Delegates;

use Minds\Core\Feeds\TwitterSync\ConnectedAccount;

interface TwitterSyncDelegateInterface
{
    /**
     * Called upon connecting a new account
     * @param ConnnectedAccount $connectedAccount
     * @return void
     */
    public function onConnect(ConnectedAccount $connectedAccount): void;
}
