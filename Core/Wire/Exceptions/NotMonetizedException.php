<?php
/**
 * Created by Marcelo.
 * Date: 28/07/2017
 */

namespace Minds\Core\Wire\Exceptions;

class NotMonetizedException extends \Exception
{
    protected $message = 'Sorry, this user cannot receive USD.';
}
