<?php
/**
 * Minds OAuth Provider.
 */

namespace Minds\Core\OAuth;

use Minds\Core\Di;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Middleware\ResourceServerMiddleware;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use Minds\Common\PseudonymousIdentifier;
use OpenIDConnectServer\ClaimExtractor;
use OpenIDConnectServer\Entities\ClaimSetEntity;
use OpenIDConnectServer\IdTokenResponse;

class Provider extends Di\Provider
{
    public function register()
    {
        $this->di->bind('OAuth\Controller', function ($di) {
            return new Controller();
        }, ['useFactory' => false]);

        // Authorization Server
        $this->di->bind('OAuth\Server\Authorization', function ($di) {
            $config = $di->get('Config');
            $clientRepository = $di->get('OAuth\Repositories\Client');
            $accessTokenRepository = $di->get('OAuth\Repositories\AccessToken');
            $scopeRepository = $di->get('OAuth\Repositories\Scope');

            $openIDClaimSets = [
                new ClaimSetEntity('openid', [ 'name', 'username' ]),
            ];

            $responseType = new IdTokenResponse(new Repositories\IdentityRepository(), new ClaimExtractor($openIDClaimSets));

            $responseType->setIssuer($config->get('site_url'));
            // Hack until OAuth2 library will support tapping into the
            if ($nonce = $di->get('OAuth\NonceHelper')->getNonce()) {
                $responseType->setNonce($nonce);
            }

            $server = new AuthorizationServer(
                $clientRepository, // instance of ClientRepositoryInterface
                $accessTokenRepository, // instance of AccessTokenRepositoryInterface
                $scopeRepository, // instance of ScopeRepositoryInterface
                $config->get('oauth')['private_key'] ?: '/var/secure/oauth-priv.key',    // path to private key
                $config->oauth['encryption_key'], // encryption key
                $responseType
            );

            // Password grant
            $server->enableGrantType(
                $di->get('OAuth\Grants\Password'),
                new \DateInterval('PT72H') // expire access token after 72 hours
            );

            // Refresh grant
            $server->enableGrantType(
                $di->get('OAuth\Grants\RefreshToken'),
                new \DateInterval('PT72H') // expire access token after 72 hours
            );

            // Implicit grant
            $server->enableGrantType(
                $di->get('OAuth\Grants\Implicit'),
                new \DateInterval('PT1H') // expire access token after 1 hour
            );

            // Auth code grant
            $server->enableGrantType(
                $di->get('OAuth\Grants\AuthCode'),
                new \DateInterval('PT1H') // expire access token after 1 hour
            );

            return $server;
        }, ['useFactory' => true]);

        // Resource Server
        $this->di->bind('OAuth\Server\Resource', function ($di) {
            $config = $di->get('Config');

            // Init our repositories
            $accessTokenRepository = $di->get('OAuth\Repositories\AccessToken');

            // Path to authorization server's public key
            $publicKeyPath = $config->get('oauth')['public_key'] ?: '/var/secure/oauth-pub.key';

            // Setup the authorization server
            $server = new ResourceServer(
                $accessTokenRepository,
                $publicKeyPath
            );

            return $server;
        }, ['useFactory' => true]);

        // Resource Server Middleware
        $this->di->bind('OAuth\Server\Resource\Middleware', function ($di) {
            return new ResourceServerMiddleware($di->get('OAuth\Server\Resource'));
        }, ['useFactory' => true]);

        // Password grant
        $this->di->bind('OAuth\Grants\Password', function ($di) {
            $grant = new PasswordGrant(
                new Repositories\UserRepository(null, null, null, new PseudonymousIdentifier()),           // instance of UserRepositoryInterface
                new Repositories\RefreshTokenRepository()    // instance of RefreshTokenRepositoryInterface
            );
            $grant->setRefreshTokenTTL(new \DateInterval('P1M')); // expire after 1 month

            return $grant;
        }, ['useFactory' => false]);

        // Refresh Token grant
        $this->di->bind('OAuth\Grants\RefreshToken', function ($di) {
            $refreshTokenRepository = $di->get('OAuth\Repositories\RefreshToken');
            $grant = new RefreshTokenGrant($refreshTokenRepository);
            $grant->setRefreshTokenTTL(new \DateInterval('P1M')); // The refresh token will expire in 1 month

            return $grant;
        }, ['useFactory' => false]);

        // Implicit grant
        $this->di->bind('OAuth\Grants\Implicit', function ($di) {
            $grant = new ImplicitGrant(new \DateInterval('PT1H'), '?');

            return $grant;
        }, ['useFactory' => false]);

        // Auth code grant
        $this->di->bind('OAuth\Grants\AuthCode', function ($di) {
            $authCodeRepository = $di->get('OAuth\Repositories\AuthCode');
            $refreshTokenRepository = $di->get('OAuth\Repositories\RefreshToken');

            $grant = new AuthCodeGrant(
                $authCodeRepository,
                $refreshTokenRepository,
                new \DateInterval('PT10M')
            );

            // OpenID Connect has issues
            $grant->disableRequireCodeChallengeForPublicClients();

            return $grant;
        }, ['useFactory' => false]);

        // Repositories
        $this->di->bind('OAuth\Repositories\RefreshToken', function ($di) {
            return new Repositories\RefreshTokenRepository();
        }, ['useFactory' => true]);

        $this->di->bind('OAuth\Repositories\AccessToken', function ($di) {
            return new Repositories\AccessTokenRepository();
        }, ['useFactory' => true]);

        $this->di->bind('OAuth\Repositories\AuthCode', function ($di) {
            return new Repositories\AuthCodeRepository();
        }, ['useFactory' => true]);

        $this->di->bind('OAuth\Repositories\User', function ($di) {
            return new Repositories\UserRepository(null, null, null, new PseudonymousIdentifier());
        }, ['useFactory' => true]);

        $this->di->bind('OAuth\Repositories\Client', function ($di) {
            return new Repositories\ClientRepository();
        }, ['useFactory' => true]);

        $this->di->bind('OAuth\Repositories\Scope', function ($di) {
            return new Repositories\ScopeRepository();
        }, ['useFactory' => true]);

        $this->di->bind('OAuth\NonceHelper', function ($di) {
            return new NonceHelper();
        }, ['useFactory' => false]);
    }
}
