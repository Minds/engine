<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\REST;
use Codeception\Util\Fixtures;
use stdClass;

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

    public function populateSupermindReplyDetails(array $supermindReply, string $dataToRetrieve): array
    {
        /**
         * @var stdClass $supermindRequestDetails
         */
        $supermindRequestDetails = Fixtures::get($dataToRetrieve);

        $supermindReply['supermind_reply_guid'] = $supermindRequestDetails->supermind->request_guid;
        $supermindReply['remind_guid'] = $supermindRequestDetails->guid;

        return $supermindReply;
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

    /**
     * @param string $dataToRetrieve
     * @return void
     * @throws ModuleException
     */
    public function rejectSupermindRequest(string $dataToRetrieve): void
    {
        /**
         * @var stdClass $details
         */
        $details = Fixtures::get('created_activity');

        /**
         * @var REST $apiClient
         */
        $apiClient = $this->getModule("REST");

        $apiClient->haveHttpHeader("Content-Type", 'application/json');
        $apiClient->send(
            "POST",
            "v3/supermind/{$details->supermind->request_guid}/reject"
        );
    }

    /**
     * Set supermind settings, passing provided array to endpoint.
     * @param array $settings - settings to update.
     * @return void
     * @throws ModuleException
     */
    public function setSupermindSettings(array $settings): void
    {
        /**
         * @var REST $apiClient
         */
        $apiClient = $this->getModule("REST");

        $apiClient->haveHttpHeader("Content-Type", 'application/json');
        $apiClient->send(
            "POST",
            "v3/supermind/settings",
            $settings
        );
    }
}
