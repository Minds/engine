<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\REST;
use Codeception\Util\Fixtures;

/**
 *
 */
class Supermind extends Module
{
    private const SUPERMIND_REQUEST_CREATION_METHOD = 'PUT';
    private const SUPERMIND_REQUEST_CREATION_ENDPOINT = 'v3/newsfeed/activity';

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

    /**
     * @param array $activityDetails
     * @return void
     * @throws ModuleException
     */
    public function createSupermindRequestWithDetails(array $activityDetails): void
    {
        /**
         * @var REST $apiClient
         */
        $apiClient = $this->getModule("REST");
        $this->loginWithSupermindRequesterAccount();

        $apiClient->send(
            self::SUPERMIND_REQUEST_CREATION_METHOD,
            self::SUPERMIND_REQUEST_CREATION_ENDPOINT,
            $activityDetails
        );
        $apiClient->seeResponseCodeIs(200);
        $activity = json_decode($apiClient->response);
        
        Fixtures::add('created_activity', $activity);
    }
}
