<?php

namespace Minds\Core\Discovery\ResponseBuilders;

use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;

class GetDiscoveryForYouResponseBuilder
{
    /**
     * @param Response $response
     * @return JsonResponse
     */
    public function buildSuccessfulResponse(Response $response, ): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'entities' => Exportable::_($response),
            'load-next' =>
        ]);
    }

    /**
     * @param ValidationErrorCollection|null $errors
     * @return void
     * @throws UserErrorException
     */
    public function buildBadRequestResponse(?ValidationErrorCollection $errors = null): void
    {
        throw new UserErrorException(
            "Some errors were encountered whilst validating the request",
            404,
            $errors
        );
    }
}
