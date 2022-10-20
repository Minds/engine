<?php

namespace Minds\Core\Authentication\Builders\Response;

use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

class AuthenticationResponseBuilder
{
    public function buildSuccessfulResponse(Response $response): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'user' => Exportable::_($response)
        ]);
    }

    /**
     * @param ValidationErrorCollection|null $errors
     * @return void
     * @throws UserErrorException
     */
    public function throwBadRequestException(?ValidationErrorCollection $errors = null): void
    {
        throw new UserErrorException(
            message: "An error occurred whilst validating the request",
            code: 400,
            errors: $errors
        );
    }
}
