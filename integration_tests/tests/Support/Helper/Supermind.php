<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Exception\ModuleException;
use Codeception\Module;

/**
 *
 */
class Supermind extends Module
{
    public function populateActivitySupermindRequestDetails(array $supermindRequest): array
    {
        $supermindRequest['receiver_guid'] = $_ENV['SUPERMIND_RECEIVER'];
        
        if ($supermindRequest['payment_options']['payment_type'] == 0) {
            $supermindRequest['payment_options']['payment_method_id'] = $_ENV['SUPERMIND_STRIPE_PAYMENT_METHOD_ID'];
        }

        return $supermindRequest;
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function loginWithSupermindRequesterAccount(): void
    {
        /**
         * @var Authentication $authentication
         */
        $authentication = $this->getModule(Authentication::class);

        $authentication->loginWithDetails(
            $_ENV['SUPERMIND_REQUESTER_USERNAME'],
            $_ENV['SUPERMIND_REQUESTER_PASSWORD']
        );
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function loginWithSupermindReceiverAccount(): void
    {
        /**
         * @var Authentication $authentication
         */
        $authentication = $this->getModule(Authentication::class);

        $authentication->loginWithDetails(
            $_ENV['SUPERMIND_RECEIVER_USERNAME'],
            $_ENV['SUPERMIND_RECEIVER_PASSWORD']
        );
    }
}
