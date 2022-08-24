<?php

namespace Minds\Core\Supermind\Payments;

use Exception;
use Minds\Core\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\User;

class SupermindPaymentProcessor
{
    const SUPERMIND_SERVICE_FEE_PCT = 10;

    public function __construct(
        private ?IntentsManagerV2 $intentsManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?MindsConfig $mindsConfig = null
    ) {
        $this->intentsManager ??= new IntentsManagerV2();
        $this->mindsConfig ??= Di::_()->get("Config");
        $this->entitiesBuilder ??= Di::_()->get("EntitiesBuilder");
    }

    /**
     * @param string $paymentMethodId
     * @param SupermindRequest $request
     * @return string
     * @throws Exception
     */
    public function setupSupermindStripePayment(string $paymentMethodId, SupermindRequest $request): string
    {
        $paymentIntent = $this->preparePaymentIntent($paymentMethodId, $request);

        $intent = $this->intentsManager->add($paymentIntent);

        return $intent->getId();
    }

    private function preparePaymentIntent(string $paymentMethodId, SupermindRequest $request): PaymentIntent
    {
        $receiver = $this->retrieveReceiverUser($request->getReceiverGuid());

        return (new PaymentIntent())
            ->setUserGuid($request->getSenderGuid())
            ->setAmount($request->getPaymentAmount())
            ->setPaymentMethod($paymentMethodId)
            ->setOffSession(true)
            ->setConfirm(false)
            ->setCaptureMethod('manual')
            ->setStripeAccountId($receiver->getMerchant()['id'])
            ->setMetadata([
                'supermind' => $request->getGuid(),
                'receiver_guid' => $request->getReceiverGuid(),
                'user_guid' => $request->getSenderGuid(),
            ])
            ->setServiceFeePct($this->mindsConfig->get('payments')['stripe']['service_fee_pct'] ?? self::SUPERMIND_SERVICE_FEE_PCT);
    }

    private function retrieveReceiverUser(string $userGuid): User
    {
        return $this->entitiesBuilder->single($userGuid);
    }


    /**
     * @param string $paymentIntentId
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelPaymentIntent(string $paymentIntentId): bool
    {
        return $this->intentsManager->cancelPaymentIntent($paymentIntentId);
    }


    public function setupSupermindOffchainPayment(SupermindRequest $request): void
    {

    }

    public function refundOffchainPayment(): void
    {

    }
}
