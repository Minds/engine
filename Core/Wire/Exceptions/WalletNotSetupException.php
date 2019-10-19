<?php

namespace Minds\Core\Wire\Exceptions;

class WalletNotSetupException extends \Exception
{
    protected $message = 'Sorry, this user cannot receive Tokens.';
}
