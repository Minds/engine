<?php


namespace Minds\Core\Email\V2\SendLists;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Connect\ManagerV2;
use Minds\Entities\User;

/**
 * A precomputed list of users active in the last 30 days
 */
class StripeConnectList extends AbstractSendList implements SendListInterface
{
    protected bool $onlyRestricted = false;

    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?ManagerV2 $stripeConnectManager = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->stripeConnectManager ??= Di::_()->get('Stripe\Connect\ManagerV2');
    }

    /**
     * Sets arguments that the cli has provided
     * @param array $cliOpts
     * @return self
     */
    public function setCliOpts(array $cliOpts = []): self
    {
        foreach ($cliOpts as $k => $v) {
            switch ($k) {
                case "only-restricted":
                    $this->onRestricted = $v;
                    break;
            }
        }

        return $this;
    }

    /**
     * Fetch all the users who have a stripe connect account. Pass --only-restricted=true to filter out
     * good standing account
     */
    public function getList(): iterable
    {
        foreach ($this->stripeConnectManager->getAll() as $account) {
            $isRestricted = !$account->charges_enabled || !$account->payouts_enabled;

            if ($this->onlyRestricted && $isRestricted) {
                continue;
            }

            $userGuid = $account->metadata->user_guid;
            $user = $this->entitiesBuilder->single($userGuid);

            if (!$user instanceof User) {
                continue;
            }

            yield $user;
        }
    }
}
