<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Exceptions;

use Minds\Exceptions\UserErrorException;

class GiftCardAlreadyClaimedException extends UserErrorException
{
    protected $code = 400;
    protected $message = 'This gift card has already been claimed';
}
