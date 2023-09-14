<?php

namespace Minds\Controllers\Cli\Supermind;

use Minds\Core\Blockchain\Wallets\OffChain\Exceptions\OffchainWalletInsufficientFundsException;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Core\Supermind\SupermindRequestStatus;
use Stripe\Exception\ApiErrorException;

/**
 * Cli script to mark Supermind requests as expired after 7 days from request creation
 */
class ExpireSupermindRequests extends \Minds\Cli\Controller implements \Minds\Interfaces\CliControllerInterface
{
    public function __construct(
        private ?Logger $logger = null
    ) {
        $this->logger ??= Di::_()->get("Logger");

        Di::_()->get("Config")
            ->set('min_log_level', 'INFO');

        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    /**
     * @inheritDoc
     */
    public function help($command = null)
    {
        $this->out("TBD");
    }

    /**
     * @return void
     * @throws ForbiddenException
     */
    public function exec(): void
    {
        $supermindManager = $this->getSupermindManager();
        $this->out("About to update expired Supermind requests");
        $supermindManager->expireRequests();
        $this->out("Updated expired Supermind requests");
    }

    /**
     * @return void
     * @throws ApiErrorException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws KeyNotSetupException
     */
    public function fix(): void
    {
        $supermindManager = $this->getSupermindManager();
        $this->out("Starting refund fix for expired Supermind requests");

        foreach ($supermindManager->getSupermindRequestsByStatus(SupermindRequestStatus::EXPIRED) as $supermindRequest) {
            if ($supermindRequest->getPaymentMethod() === SupermindRequestPaymentMethod::CASH) {
                continue;
            }

            $this->out("===============================");
            $this->out("Checking reimburse of {$supermindRequest->getPaymentAmount()} token(s) to user {$supermindRequest->getSenderGuid()} for Supermind {$supermindRequest->getGuid()}");
            if (!$supermindManager->isSupermindRequestRefunded($supermindRequest->getGuid())) {
                if (
                    !$this->getOpt('dry-run')
                ) {
                    $supermindManager->reimburseSupermindPayment($supermindRequest);
                    $this->out("Tokens refunded");
                }
            } else {
                $this->out("Tokens already refunded");
            }
        }
    }

    private function getSupermindManager(): SupermindManager
    {
        return new SupermindManager();
    }
}
