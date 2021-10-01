<?php
namespace Minds\Core\Feeds\TwitterSync\Delegates;

use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Core\Feeds\TwitterSync\ConnectedAccount;

class ChannelLinksDelegate implements TwitterSyncDelegateInterface
{
    public function __construct(protected EntitiesBuilder $entitiesBuilder)
    {
    }

    /**
     * Called upon connecting a new account
     * @param ConnnectedAccount $connectedAccount
     * @return void
     */
    public function onConnect(ConnectedAccount $connectedAccount): void
    {
        /** @var User */
        $user = $this->entitiesBuilder->single($connectedAccount->getUserGuid());

        $socialProfiles = $user->getSocialProfiles(); // See api/v1/channels.php for more

        foreach ($socialProfiles as $k => $socialProfile) {
            if ($socialProfile['key'] === 'twitter') {
                return; // Already exists a twitter account so do nothing more
            }
        }

        $socialProfiles[] = [
            'key' => 'twitter',
            'value' => $connectedAccount->getTwitterUser()->getUsername(),
        ];

        $user->setSocialProfiles(array_values($socialProfiles))->save();
    }
}
