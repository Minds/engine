<?php
/**
 * Exceptions that can be rendered to the user in a
 * safe way
 */
namespace Minds\Exceptions;

use Minds\Entities\ValidationErrorCollection;

class UserErrorException extends \Exception
{
    private ?ValidationErrorCollection $errors;

    /** @var string */
    protected $message = "An unknown error occurred";

    public function __construct($message = "", $code = 0, ?ValidationErrorCollection $errors = null)
    {
        parent::__construct(
            !empty($message) ? $message : $this->message,
            $code
        );
        $this->setErrors($errors);
    }

    /**
     * Set the validation errors collection to be returned with the exception
     * @param ValidationErrorCollection|null $errors
     * @return $this
     */
    public function setErrors(?ValidationErrorCollection $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Returns the collection of validation errors
     * @return ValidationErrorCollection|null
     */
    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }
}
