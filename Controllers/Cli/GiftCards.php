<?php

namespace Minds\Controllers\Cli;

use Exception;
use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Manager;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Entities\User;
use Minds\Interfaces;

class GiftCards extends Cli\Controller implements Interfaces\CliControllerInterface
{
    private Manager $giftCardsManager;
    private PaymentsManager $paymentsManager;
    private EntitiesBuilder $entitiesBuilder;

    public function __construct(
    )
    {
        $this->giftCardsManager = Di::_()->get(Manager::class);
        $this->paymentsManager = Di::_()->get(PaymentsManager::class);
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
        Di::_()->get('Config')
          ->set('min_log_level', 'INFO');
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    /**
     * @return void
     */
    public function exec(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    public function createTestCard()
    {
        $userGuid = $this->getOpt('user_guid');

        $user = $this->entitiesBuilder->single($userGuid);

        if (!$user instanceof User) {
            throw new Exception("Invalid user");
        }

        $giftCard = $this->giftCardsManager->createGiftCard(
            issuer: $user,
            productId: GiftCardProductIdEnum::BOOST,
            amount: 10,
        );

        var_dump($giftCard);
    }
}