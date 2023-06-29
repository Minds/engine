<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Exceptions;

class InvalidGiftCardClaimCodeException extends \Minds\Exceptions\UserErrorException
{
    protected $code = 400;
    protected $message = 'Invalid gift card claim code';
}
