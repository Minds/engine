<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Exceptions;

use Minds\Exceptions\NotFoundException;

class GiftCardNotFoundException extends NotFoundException
{
    protected $code = 404;
    protected $message = 'Gift card not found';
}
