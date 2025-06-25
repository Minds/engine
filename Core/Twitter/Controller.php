<?php

declare(strict_types=1);

namespace Minds\Core\Twitter;

use Minds\Core\Di\Di;
use Minds\Core\Router\Enums\RequestAttributeEnum;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;

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
     * @throws InvalidArgumentException
     */
    public function requestTwitterOAuthToken(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        $url = $this->manager->getRequestOAuthAuthorizationCodeUrl();

        return new JsonResponse(['authorization_url' => $url]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return RedirectResponse
     * @throws InvalidArgumentException
     */
    public function redirectToTwitterAuthUrl(ServerRequestInterface $request): RedirectResponse
    {
        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        $url = $this->manager->getRequestOAuthAuthorizationCodeUrl();

        return new RedirectResponse($url);
    }

    /**
     * @param ServerRequestInterface $request
     * @return HtmlResponse
     * @throws InvalidArgumentException
     */
    public function generateTwitterOAuthAccessToken(ServerRequestInterface $request): HtmlResponse
    {
        $authorizationCode = $request->getQueryParams()['code'] ?? null;

        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        if ($authorizationCode) {
            $this->manager->generateOAuthAccessToken($authorizationCode);
        } else {
            // Oops, there was a problem
        }

        $cspNonce = $request->getAttribute(RequestAttributeEnum::CSP_NONCE);
        return new HtmlResponse(
            <<<HTML
<script nonce="$cspNonce">window.close();</script>
<p>Please close this window/tab.</p>
HTML
        );
    }
    // Row 3: 991438404115763219

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exceptions\TwitterDetailsNotFoundException
     */
    public function postTweet(ServerRequestInterface $request): JsonResponse
    {
        $requestBody = $request->getParsedBody();

        $loggedInUser = $request->getAttribute('_user');
        $this->manager->setUser($loggedInUser);

        $response = $this->manager->postTextTweet($requestBody['tweet_text']);
        return new JsonResponse($response);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function getUserConfig(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $this->manager->setUser($loggedInUser);

        $response = $this->manager->getDetails();
        return new JsonResponse($response->export());
    }
}
