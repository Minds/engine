<?php

/**
 * Upgrades Delegate
 */

namespace Minds\Core\Wire\Delegates;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Wire\Wire;
use Minds\Core\Pro\Manager as ProManager;
use Minds\Entities\User;
use Minds\Core\Plus\Subscription as PlusSubscription;

class UpgradesDelegate
{
    /** @var Config */
    private $config;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var ProManager */
    private $proManager;

    /** @var Logger */
    private $logger;

    public function __construct($config = null, $entitiesBuilder = null, $proManager = null, $logger = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->proManager = $proManager ?? Di::_()->get('Pro\Manager');
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    /**
     * On Wire
     * @param Wire $wire
     * @param string $receiver_address
     * @return Wire $wire
     */
    public function onWire($wire, $receiver_address): Wire
    {
        switch ($wire->getReceiver()->guid) {
            case $this->config->get('plus')['handler']:
                return $this->onPlusUpgrade($wire, $receiver_address);
                break;
            case $this->config->get('pro')['handler']:
                return $this->onProUpgrade($wire, $receiver_address);
                break;
        }
        return $wire; // Not expected
    }

    /**
     * If plus wire detected
     * @param Wire $wire
     * @param string $receiver_address - (eth address or guid)
     * @return Wire
     */
    private function onPlusUpgrade($wire, $receiver_address): Wire
    {
        //set the plus period for this user
        $user = $wire->getSender();

        // rebuild the user as we can't trust upstream
        /** @var User */
        $user = $this->entitiesBuilder->single($user->getGuid(), [
            'cache' => false,
        ]);

        if (!$user) {
            return $wire;
        }

        $days = 30;
        $monthly = $this->config->get('upgrades')['plus']['monthly'];
        $yearly = $this->config->get('upgrades')['plus']['yearly'];
        $lifetime = $this->config->get('upgrades')['plus']['lifetime'];

        switch ($wire->getMethod()) {
            case 'tokens':
                $user->setPlusMethod('tokens');
                if ($lifetime['tokens'] == $wire->getAmount() / (10 ** 18)) {
                    $days = 36500; // 100 years
                } else {
                    return $wire;
                }
                break;
            case 'usd':
                $user->setPlusMethod('usd');
                if ($user->plus_expires > strtotime('40 days ago') && $wire->getAmount() == 500 && php_sapi_name() === 'cli') {
                    // If user has had Minds+ before, in the last billing period, and we are running via the CLI
                    // treat as legacy subscription customer
                    $days = 32;
                    break;
                }
                // Users who have never had Minds+ before get a 7 day trial
                // we still create the subscription, but do no charge for 7 days
                if ($wire->getTrialDays()) {
                    $days = 9; // We charge on day 7, allow a buffer in case subscripton charge is late
                } elseif ($monthly['usd'] == $wire->getAmount() / 100) {
                    $days = 32;
                } elseif ($yearly['usd'] == $wire->getAmount() / 100) {
                    $days = 368;
                } else {
                    return $wire;
                }
                break;
            default:
                return $wire;
        }

        $expires = strtotime("+{$days} days", $wire->getTimestamp());

        $user->setPlusExpires($expires);
        $user->save();

        return $wire;
    }

    private function onProUpgrade($wire, $receiver_address): Wire
    {
        //set the plus period for this user
        $user = $wire->getSender();

        // rebuild the user as we can't trust upstream
        /** @var User */
        $user = $this->entitiesBuilder->single($user->getGuid(), [
            'cache' => false,
        ]);

        if (!$user) {
            return $wire;
        }

        $days = 30;
        $monthly = $this->config->get('upgrades')['pro']['monthly'];
        $yearly = $this->config->get('upgrades')['pro']['yearly'];
        $lifetime = $this->config->get('upgrades')['pro']['lifetime'];

        error_log($wire->getMethod());
        switch ($wire->getMethod()) {
            case 'tokens':
                $user->setProMethod('tokens');
                if ($lifetime['tokens'] == $wire->getAmount() / (10 ** 18)) {
                    $days = 36500; // 100 years
                } else {
                    return $wire;
                }
                break;
            case 'usd':
                $user->setProMethod('usd');
                if ($monthly['usd'] == $wire->getAmount() / 100) {
                    $days = 32;
                } elseif ($yearly['usd'] == $wire->getAmount() / 100) {
                    $days = 367;
                } else {
                    return $wire;
                }
                break;
            default:
                return $wire;
        }

        $expires = strtotime("+{$days} days", $wire->getTimestamp());

        $this->proManager->setUser($user)
            ->enable($expires);

        $this->cancelExistingPlus($user);

        return $wire;
    }

    /**
     * Cancels an existing plus subscription upon subscribing to Pro (if one exists).     *
     * @param User $user - user to cancel for.
     * @return void
     */
    private function cancelExistingPlus(User $user): void
    {
        try {
            $plusSubscription = (new PlusSubscription())
                        ->setUser($user);

            if ($plusSubscription->canBeCancelled()) {
                $plusSubscription->cancel();
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
