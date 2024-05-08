<?php declare(strict_types=1);
/**
 * ForbiddenException
 * @author edgebal
 */

namespace Minds\Core\Router\Exceptions;

use Exception;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLExceptionInterface;

class ForbiddenException extends Exception implements GraphQLExceptionInterface
{
    /**
     * Returns the "extensions" object attached to the GraphQL error.
     * @return array<string, mixed>
     */
    public function getExtensions(): array
    {
        return [];
    }
    
    /**
     * Returns true when exception message is safe to be displayed to a client.
     * @return bool
     */
    public function isClientSafe(): bool
    {
        return true;
    }
}
