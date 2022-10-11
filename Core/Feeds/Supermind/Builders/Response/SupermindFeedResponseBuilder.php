<?php

declare(strict_types=1);

namespace Minds\Core\Feeds\Supermind\Builders\Response;

use Minds\Api\Exportable;
use Minds\Common\Repository\Response;
use Minds\Core\Feeds\Supermind\Exceptions\SupermindFeedBadRequestException;
use Minds\Entities\ValidationErrorCollection;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Responsible for handling the response objects that the Supermind feed endpoint returns
 */
class SupermindFeedResponseBuilder
{
    public function buildSuccessfulResponse(Response $response): JsonResponse
    {
        return new JsonResponse(
            [
                'status' => 'success',
                'entities' => Exportable::_($response),
                'load-next' => $response->getPagingToken()
            ],
            200,
            [],
            JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    /**
     * @param ValidationErrorCollection|null $errors
     * @return void
     * @throws SupermindFeedBadRequestException
     */
    public function throwBadRequestResponse(?ValidationErrorCollection $errors): void
    {
        throw new SupermindFeedBadRequestException(errors: $errors);
    }
}
