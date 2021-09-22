<?php
namespace Minds\Core\OAuth;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Minds\Common\IpAddress;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\OAuth\Delegates\EventsDelegate;
use Minds\Core\OAuth\Entities\UserEntity;
use Minds\Core\OAuth\Repositories\AccessTokenRepository;
use Minds\Core\OAuth\Repositories\ClientRepository;
use Minds\Core\OAuth\Repositories\RefreshTokenRepository;
use Minds\Core\Security\Password\RateLimits;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * OAuth Controller
 * @package Minds\Core\OAuth
 */
class Controller
{
    /** @var Config */
    protected $config;

    /** @var AuthorizationServer */
    protected $authorizationServer;

    /** @var AccessTokenRepository */
    protected $accessTokenRepository;

    /** @var RefreshTokenRepository */
    protected $refreshTokenRepository;

    /** @var ClientRepository */
    protected $clientRepository;

    /** @var NonceHelper */
    protected $nonceHelper;

    /** @var EventsDelegates */
    protected $eventsDelegate;

    /** @var RateLimits */
    protected $passwordRateLimits;

    public function __construct(
        Config $config = null,
        AuthorizationServer $authorizationServer = null,
        AccessTokenRepository $accessTokenRepository = null,
        RefreshTokenRepository $refreshTokenRepository = null,
        ClientRepository $clientRepository = null,
        NonceHelper $nonceHelper = null,
        EventsDelegate $eventsDelegate = null
    ) {
        $this->config = $config ?? Di::_()->get('Config');
        $this->authorizationServer = $authorizationServer ?? Di::_()->get('OAuth\Server\Authorization');
        $this->accessTokenRepository = $accessTokenRepository ?? Di::_()->get('OAuth\Repositories\AccessToken');
        $this->refreshTokenRepository = $refreshTokenRepository ?? Di::_()->get('OAuth\Repositories\RefreshToken');
        $this->clientRepository = $clientRepository ?? Di::_()->get('OAuth\Repositories\Client');
        $this->nonceHelper = $nonceHelper ?? Di::_()->get('OAuth\NonceHelper');
        $this->eventsDelegate = $eventsDelegate ?? new EventsDelegate;
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function authorize(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        try {
            $authRequest = $this->authorizationServer->validateAuthorizationRequest($request);

            $userEntity = new UserEntity();
            $userEntity->setIdentifier($user->getGuid());

            /**
             * This is not ideal, but we are doing it because the OAuth library
             * does not support accessing the auth code entity on response
             */
            $queryParams = $request->getQueryParams();
            if (isset($queryParams['nonce'])) {
                $this->nonceHelper->setNonce($user->getGuid(), $queryParams['nonce']);
            }

            $authRequest->setUser($userEntity);
            $authRequest->setRedirectUri($authRequest->getClient()->getRedirectUri());

            // If client is matrix, auto approve without asking user consent.
            if ($authRequest->getClient()->getIdentifier() === 'matrix') {
                $authRequest->setAuthorizationApproved(true);
            }
            
            // Return the HTTP redirect response
            $response = $this->authorizationServer->completeAuthorizationRequest($authRequest, new JsonResponse([]));

            // Trigger the events delegate
            $this->eventsDelegate->onAuthorizeSuccess($authRequest);

            return $response;
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse(new JsonResponse([]));
        }
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function token(ServerRequest $request): JsonResponse
    {
        $response = new JsonResponse([]);

        /**
         * Hack as some matrix is not sending client_id
         **/
        $payload = $request->getParsedBody();
        if (!isset($payload['client_id'])) {
            $client = $this->clientRepository->getClientEntity('matrix');

            if ($client->getRedirectUri() === $payload['redirect_uri']) {
                $payload['client_id'] = $client->getIdentifier();
            }
            $request = $request->withParsedBody($payload);
        }

        try {
            $response = $this->authorizationServer->respondToAccessTokenRequest($request, $response);
            $body = json_decode($response->getBody(), true);
            $body['status'] = 'success';
            $response = new JsonResponse($body);
        } catch (OAuthServerException $e) {
            // \Sentry\captureException($e);
            $response = $e->generateHttpResponse($response);
        } catch (\Exception $exception) {
            $body = [
                'status' => 'error',
                'error' => $exception->getMessage(),
                'message' => $exception->getMessage(),
                'errorId' => str_replace('\\', '::', get_class($exception)),
            ];
            $response = new JsonResponse($body, $exception->getCode());
        }

        return $response;
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function revoke(ServerRequest $request): JsonResponse
    {
        $response = new JsonResponse([]);

        try {
            /** @var string */
            $tokenId = $request->getAttribute('oauth_access_token_id');

            $this->accessTokenRepository->revokeAccessToken($tokenId);

            $refreshToken = $this->refreshTokenRepository->getRefreshTokenFromAccessTokenId($tokenId);
            if ($refreshToken) {
                $this->refreshTokenRepository->revokeRefreshToken($refreshToken->getIdentifier());
            }

            // remove surge token for push notifications.
            $user = $request->getAttribute('_user');
            $user->setSurgeToken('');
            
            $save = new Save();
            $save->setEntity($user)
              ->save();
            
            $response = new JsonResponse([]);
        } catch (\Exception $e) {
            // \Sentry\captureException($e); // Log to sentry
            throw new UserErrorException($e->getMessage(), 500);
        }

        return $response;
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function userinfo(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');
        
        return new JsonResponse([
            'sub' => (string) $user->getGuid(),
            'name' => $user->getName(),
            'username' => $user->getUsername(),
        ]);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function jwks(ServerRequest $request): JsonResponse
    {
        $pem = file_get_contents($this->config->get('oauth')['public_key']);

        $options = [
            'use' => 'sig',
            'alg' => 'RS256',
            //'kid' => hash('sha512', $pem),
        ];

        $keyFactory = new \Strobotti\JWK\KeyFactory();
        $key = $keyFactory->createFromPem($pem, $options);

        return new JsonResponse([
            'keys' => [
                json_decode((string) $key, true)
            ]
        ]);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getOpenIDConfiguration(ServerRequest $request): JsonResponse
    {
        $discovery = [
            'issuer' => $this->config->get('site_url'),
            'authorization_endpoint' =>  $this->config->get('site_url') . 'api/v3/oauth/authorize',
            'token_endpoint' => $this->config->get('site_url') . 'api/v3/oauth/token',
            'revocation_endpoint' => $this->config->get('site_url') . 'api/v3/oauth/revoke',
            // 'introspection_endpoint' => $this->config->get('site_url') . 'api/v3/oauth/introspect',
            'userinfo_endpoint' => $this->config->get('site_url') . 'api/v3/oauth/userinfo',
            'jwks_uri' => $this->config->get('site_url') . 'api/v3/oauth/jwks',
            'scopes_supported' => [
                'openid',
            ],
            'response_types_supported' => [
                'code',
                'token'
            ],
            'response_modes_supported' => [
                'query',
            ],
            'grant_types_supported' => [
                'authorization_code',
                'refresh_token',
            ],
            'token_endpoint_auth_methods_supported' => [
                'client_secret_basic',
                'client_secret_post'
            ],
            'subject_types_supported' => [
                'public'
            ],
            'id_token_signing_alg_values_supported' => [
                'RS256'
            ],
            'claim_types_supported' => [
                'normal'
            ],
            'claims_supported' => [
                'iss',
                'sub',
                'name',
                'username',
                'nonce',
            ]
        ];

        return new JsonResponse($discovery, 200);
    }
}
