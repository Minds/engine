<?php
namespace Minds\Core\Payments\Stripe\Exceptions;

use Minds\Exceptions\ServerErrorException;

class StripeNotConfiguredException extends ServerErrorException
{
    protected $message = 'Stripe has not been setup';
}
