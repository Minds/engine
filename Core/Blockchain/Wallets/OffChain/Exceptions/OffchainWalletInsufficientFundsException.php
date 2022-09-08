<?php

namespace Minds\Core\Blockchain\Wallets\OffChain\Exceptions;

use Minds\Exceptions\UserErrorException;

/**
 * Exception thrown when trying to perform an off-chain transaction from a wallet for an amount higher than the funds available
 */
class OffchainWalletInsufficientFundsException extends UserErrorException
{
    protected $code = 400;
    protected $message = "The funds available are insufficient to complete the operation";
}
