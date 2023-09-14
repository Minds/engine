<?php

declare(strict_types=1);

namespace Minds\Core\Authentication;

use Exception;
use Minds\Core\Authentication\Builders\Response\AuthenticationResponseBuilder;
use Minds\Core\Authentication\Exceptions\AuthenticationAttemptsExceededException;
use Minds\Core\Authentication\Validators\AuthenticationRequestValidator;
use Minds\Core\Di\Di;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\Exceptions\UserNotSetupException;
use Minds\Core\Security\TwoFactor\TwoFactorInvalidCodeException;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use RedisException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Authentication\Manager');
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws AuthenticationAttemptsExceededException
     * @throws NotFoundException
     * @throws TwoFactorInvalidCodeException
     * @throws TwoFactorRequiredException
     * @throws UnauthorizedException
     * @throws UserErrorException
     * @throws UserNotSetupException
     * @throws RedisException
     */
    //    #[OA\Post(
    //        path: '/api/v3/authenticate',
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 401, description: "Unauthorized"),
    //            new OA\Response(response: 403, description: "Forbidden")
    //        ]
    //    )]
    public function authenticate(ServerRequest $request): JsonResponse
    {
        $requestValidator = new AuthenticationRequestValidator();
        $responseBuilder = new AuthenticationResponseBuilder();

        if (!$requestValidator->validate($request->getParsedBody())) {
            $responseBuilder->throwBadRequestException($requestValidator->getErrors());
        }

        ['username' => $username, 'password' => $password] = $request->getParsedBody();
        $response = $this->manager->authenticate($username, $password, $request);

        return $responseBuilder->buildSuccessfulResponse($response);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exception
     */
    //    #[OA\Delete(
    //        path: '/api/v3/authenticate',
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 401, description: "Unauthorized"),
    //            new OA\Response(response: 403, description: "Forbidden")
    //        ]
    //    )]
    public function deleteSession(ServerRequestInterface $request): JsonResponse
    {
        $this->manager->deleteSession();
        return new JsonResponse(['status' => 'success']);
    }


    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    //    #[OA\Delete(
    //        path: '/api/v3/authenticate/all',
    //        responses: [
    //            new OA\Response(response: 200, description: "Ok"),
    //            new OA\Response(response: 401, description: "Unauthorized"),
    //            new OA\Response(response: 403, description: "Forbidden")
    //        ]
    //    )]
    public function deleteAllUserSessions(ServerRequestInterface $request): JsonResponse
    {
        $this->manager->deleteAllUserSessions();
        return new JsonResponse(['status' => 'success']);
    }
}
