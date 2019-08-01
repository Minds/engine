<?php
/**
 * Plus Delegate
 */
namespace Minds\Core\Wire\Delegates;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Wire\Manager;

class Plus
{   
    /* @var const LIFETIME_TIMESTAMP */
    const LIFETIME_TIMESTAMP = 9999999999;

    /** @var Config $config */
    private $config;

    /** @var EntitiesBuilder $entitiesBuilder */
    private $entitiesBuilder;

    public function __construct($config = null, $entitiesBuilder = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * To be called on an incoming wire.
     * @param Wire $wire - the wire object.
     * @param string $receiver_address - the recieving address.
     * @return Wire $wire - the wire object.
     */
    public function onWire($wire, $receiver_address, $tier = null)
    {
        if ($wire->getReceiver()->guid != $this->config->get('blockchain')['contracts']['wire']['plus_guid']) {
            return $wire; //not sent to plus
        }

        if (
            !(
                $receiver_address == 'offchain'
                || $receiver_address == $this->config->get('blockchain')['contracts']['wire']['plus_address']
            )
        ) {
            return $wire; //not offchain or potential onchain fraud 
        }

        // 20 tokens, 180 tokens, 500 tokens.
        if (($wire->getAmount() != "20000000000000000000" && $receiver_address === 'offchain')        // 20 offchain
            && ($wire->getAmount() != "200000000000000000000" && $receiver_address === 'offchain')    // 200 offchain
            && ($wire->getAmount() != "5000000000000000000000" && $receiver_address === 'offchain')   // 5000 offchain
            && ($wire->getAmount() != "40000000000000000000" && $receiver_address !== 'offchain')     // 40 onchain
            && ($wire->getAmount() != "400000000000000000000" && $receiver_address !== 'offchain')) { // 400 onchain
            return $wire; //incorrect wire amount sent
        }

        //set the plus period for this user
        $user = $wire->getSender();

        // rebuild the user as we can't trust upstream
        $user = $this->entitiesBuilder->single($user->getGuid(), [
            'cache' => false,
        ]);

        if (!$user) {
            return $wire;
        }

        // check the users tier if passed in. If not, it's a standard monthly subscription.
        switch ($tier) {
            case Manager::LIFETIME:
                $user->setPlusExpires(static::LIFETIME_TIMESTAMP); //life
                break;

            case Manager::YEARLY:
                $user->setPlusExpires($this->calculatePlusExpires('+1 year', $wire->getTimestamp(), $user->plus_expires));
                break;

            default:
                $user->setPlusExpires($this->calculatePlusExpires('+30 days', $wire->getTimestamp(), $user->plus_expires));
                break;
        }

        $user->save();

        //$wire->setSender($user);
        return $wire;
    }

    /**
     * Calculates a user's plus expirey date - factoring in upgrades to existing subscriptions. 
     *
     * @param [String] $timespan - first param of strtotime().
     * @param [Integer] $wireTimestamp - the unix timestamp on the wire transaction. 
     * @param [Integer] $previousTimestamp - the users previous subscription unix timestamp.
     * @return [Integer] the new unix expiry date. 
     */
    public function calculatePlusExpires($timespan, $wireTimestamp, $previousTimestamp = null) 
    {
        if ($previousTimestamp === static::LIFETIME_TIMESTAMP) {
            throw new \Exception('Already existing lifetime subscription');
        }

        if($previousTimestamp === null || $previousTimestamp < time()) {
            return strtotime($timespan, $wireTimestamp);
        }

        return strtotime($timespan, $previousTimestamp);
    }
}
