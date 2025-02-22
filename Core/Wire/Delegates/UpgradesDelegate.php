<?php

/**
 * Upgrades Delegate
 */

namespace Minds\Core\Wire\Delegates;

use Minds\Common\SystemUser;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Plus\Subscription as PlusSubscription;
use Minds\Core\Pro\Manager as ProManager;
use Minds\Core\Wire\Wire;
use Minds\Entities\User;

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

    private Save $save;

    public function __construct(
        $config = null,
        $entitiesBuilder = null,
        $proManager = null,
        $logger = null,
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->proManager = $proManager ?? Di::_()->get('Pro\Manager');
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->save = new Save();
    }

    /**
     * On Wire
     * @param Wire $wire
     * @param string $receiver_address
     * @return Wire $wire
     */
    public function onWire($wire, $receiver_address): Wire
    {
        $result = match ($wire->getReceiver()->getGuid()) {
            $this->config->get('plus')['handler'] => function () use ($wire, $receiver_address): string {
                $this->onPlusUpgrade($wire, $receiver_address);
                return "plus";
            },
            $this->config->get('pro')['handler'] => function () use ($wire, $receiver_address): string {
                $this->onProUpgrade($wire, $receiver_address);
                return "pro";
            },
            default => null
        };

        if (!$result) {
            return $wire;
        }

        $wireType = $result();

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
        if ($user->plus_expires > time()) {
            $expires = strtotime("+{$days} days", $user->plus_expires);
        }

        $user->setPlusExpires($expires);

        $this->save
            ->setEntity($user)
            ->withMutatedAttributes([
                'plus_expires',
            ])
            ->save();

        $wire->getSender()->setPlusExpires($expires);

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
        if ($user->pro_expires > time()) {
            $expires = strtotime("+{$days} days", $user->pro_expires);
        }


        $this->proManager->setUser($user)
            ->enable($expires);

        $wire->getSender()->setProExpires($expires);

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
