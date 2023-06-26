<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli;

use Exception;
use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardPaymentFailedException;
use Minds\Core\Payments\GiftCards\Manager;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Minds\Interfaces;
use Stripe\Exception\ApiErrorException;

class GiftCards extends Cli\Controller implements Interfaces\CliControllerInterface
{
    private Manager $giftCardsManager;
    private PaymentsManager $paymentsManager;
    private EntitiesBuilder $entitiesBuilder;

    private readonly Logger $logger;

    public function __construct(
    ) {
        $this->giftCardsManager = Di::_()->get(Manager::class);
        $this->paymentsManager = Di::_()->get(PaymentsManager::class);
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $this->logger = Di::_()->get('Logger');

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

    /**
     * @return void
     * @throws GiftCardPaymentFailedException
     * @throws StripeTransferFailedException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws ApiErrorException
     * @throws Exception
     */
    public function createTestCard(): void
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
            stripePaymentMethodId: $this->getOpt('stripe_payment_method_id'),
            targetUserGuid: (int) $this->getOpt('target_user_guid') ?? null,
            targetEmail: $this->getOpt('target_email') ?? null,
        );

        $this->logger->info('Gift card created', [
            'gift_card_guid' => $giftCard->guid,
            'gift_card_code' => $giftCard->claimCode,
        ]);
    }

    public function getGiftCard()
    {
        $guid = $this->getOpt('guid');

        $giftCard = $this->giftCardsManager->getGiftCard($guid);

        var_dump($giftCard);
    }

    public function getUserBalance()
    {
        $userGuid = $this->getOpt('user_guid');

        $user = $this->entitiesBuilder->single($userGuid);

        if (!$user instanceof User) {
            throw new Exception("Invalid user");
        }

        $this->out($this->giftCardsManager->getUserBalanceByProduct($user));
    }

    public function getUserTransactions()
    {
        $userGuid = $this->getOpt('user_guid');

        $user = $this->entitiesBuilder->single($userGuid);

        if (!$user instanceof User) {
            throw new Exception("Invalid user");
        }

        //$hasMore = false;

        $transactions = iterator_to_array($this->giftCardsManager->getUserTransactions($user, limit: 5, hasMore: $hasMore));

        var_dump($transactions, $hasMore);
    }

    public function createSpend()
    {
        $userGuid = $this->getOpt('user_guid');

        $user = $this->entitiesBuilder->single($userGuid);

        if (!$user instanceof User) {
            throw new Exception("Invalid user");
        }

        $amount = $this->getOpt('amount');

        $paymentDetails = new PaymentDetails([
            'paymentAmountMillis' => (int) round($amount * 1000),
            'userGuid' => (int) $user->getGuid(),
            'paymentType' => 0,
            'paymentMethod' => 0,
        ]);
        $this->paymentsManager->createPayment($paymentDetails);

        $this->giftCardsManager->spend(
            user: $user,
            productId: GiftCardProductIdEnum::BOOST,
            payment: $paymentDetails,
        );
    }
}
