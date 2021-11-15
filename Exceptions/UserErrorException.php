<?php
/**
 * Exceptions that can be rendered to the user in a
 * safe way
 */
namespace Minds\Exceptions;

use Minds\Entities\ValidationErrorCollection;
use Minds\Traits\MagicAttributes;

/**
 * @method ValidationErrorCollection getErrors()
 * @method self setErrors(ValidationErrorCollection $errors)
 */
class UserErrorException extends \Exception
{
    use MagicAttributes;

    public function __construct($message = "", $code = 0, ?ValidationErrorCollection $errors = null)
    {
        parent::__construct($message, $code);
        $this->setErrors($errors);
    }

    /** @var string */
    protected $message = "An unknown error occurred";
}
