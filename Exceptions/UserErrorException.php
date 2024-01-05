<?php
/**
 * Exceptions that can be rendered to the user in a
 * safe way
 */
namespace Minds\Exceptions;

use Minds\Entities\ValidationErrorCollection;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLExceptionInterface;

class UserErrorException extends \Exception implements GraphQLExceptionInterface
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

    // Graphql Interface

    /**
     * Returns true when exception message is safe to be displayed to a client.
     */
    public function isClientSafe(): bool
    {
        return true;
    }

    /**
     * Returns string describing a category of the error.
     *
     * Value "graphql" is reserved for errors produced by query parsing or validation, do not use it.
     */
    public function getCategory(): string
    {
        return 'VALIDATION';
    }

    /**
     * Returns the "extensions" object attached to the GraphQL error.
     *
     * @return array<string, mixed>
     */
    public function getExtensions(): array
    {
        return [];
    }
}
