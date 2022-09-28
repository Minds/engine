<?php

declare(strict_types=1);

namespace Minds\Core\Twitter;

use Minds\Core\Di\Di;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * The controller for the Twitter module's endpoints
 */
class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Twitter\Manager');
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function requestTwitterOAuthToken(ServerRequestInterface $request): JsonResponse
    {
        $url = $this->manager->getRequestOAuthAuthorizationCodeUrl();

        return new JsonResponse(['authorization_url' => $url]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function generateTwitterOAuthAccessToken(ServerRequestInterface $request): JsonResponse
    {
        $authorizationCode = $request->getQueryParams()['code'] ?? null;

        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        $this->manager->generateOAuthAccessToken($authorizationCode);

        return new JsonResponse([]);
    }

    public function postTweet(ServerRequestInterface $request): JsonResponse
    {
        $requestBody = $request->getParsedBody();

        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        $response = $this->manager->postTweet($requestBody['tweet_text']);
        return new JsonResponse($response);
    }
}
