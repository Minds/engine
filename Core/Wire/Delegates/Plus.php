<?php
/**
 * Plus Delegate
 */
namespace Minds\Core\Wire\Delegates;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;

class Plus
{
    /** @var Config $config */
    private $config;

    /** @var EntitiesBuilder $entitiesBuilder */
    private $entitiesBuilder;

    private Save $save;

    public function __construct(
        $config = null,
        $entitiesBuilder = null,
        $save = null,
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->save = $save ?? new Save();
    }

    /**
     * On Wire
     * @param Wire $wire
     * @param string $receiver_address
     * @return Wire $wire
     */
    public function onWire($wire, $receiver_address)
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

        // 20 tokens
        if ($wire->getAmount() != "20000000000000000000") {
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

        $user->setPlusExpires(strtotime('+31 days', $wire->getTimestamp()));

        $this->save
            ->setEntity($user)
            ->withMutatedAttributes([
                'plus_expires',
            ])
            ->save();

        //$wire->setSender($user);
        return $wire;
    }
}
