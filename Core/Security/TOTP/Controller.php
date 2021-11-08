<?php
namespace Minds\Core\Security\TOTP;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Exception;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Security\TwoFactor;
use Minds\Core\Security\TOTP\TOTPSecret;
use Minds\Core\Security\TOTP\Manager;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * TOTP Controller
 * @package Minds\Core\Security\TOTP
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /** @var TwoFactor */
    protected $twoFactor;

    /** @var TwoFactor\Manager */
    protected $twoFactorManager;

    /**
     * Controller constructor.
     * @param null $manager
     * @param null $twoFactor
     * @param null $twoFactorManager
     */
    public function __construct(
        $manager = null,
        $twoFactor = null,
        $twoFactorManager = null
    ) {
        $this->manager = $manager ?? new Manager();
        $this->twoFactor = $twoFactor ?? Di::_()->get('Security\TwoFactor');
        $this->twoFactorManager = $twoFactorManager ?? Di::_()->get('Security\TwoFactor\Manager');
    }

    /**
     * Returns a new secret for user to use when
     * setting up 3rd-party TOTP authenticator app
     * @return JsonResponse
     * @throws Exception when user is already registered
     *
     */
    public function createNewSecret(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $isRegistered = $this->manager->isRegistered($user);

        if ($isRegistered) {
            throw new Exception("Secret already exists for this user");
        }

        $secret = $this->twoFactor->createSecret();

        return new JsonResponse([
            'status' => 'success',
            'secret' => $secret,
        ]);
    }

    /**
     * Saves secret if user has provided valid code
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     * @throws UserErrorException
     */
    public function authenticateDevice(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $body = $request->getParsedBody();
        $secret = $body['secret'] ?? '';
        $code = (string) $body['code'] ?? '';

        // Require previous two factor (default email) before proceeding
        $this->twoFactorManager->gatekeeper($user, $request);

        $codeIsValid = $this->twoFactor->verifyCode($secret, $code, 10);

        if (!$codeIsValid) {
            throw new UserErrorException("Invalid code");
        }

        /**
         * Create a random recovery code, return it to the user and store its hash
         */
        $recoveryCode = hash('sha512', openssl_random_pseudo_bytes(128));
        $recoveryHash = password_hash($recoveryCode, PASSWORD_BCRYPT);

        $totpSecret = new TOTPSecret();
        $totpSecret->setUserGuid($user->getGuid())
            ->setSecret($secret)
            ->setrecoveryHash($recoveryHash);

        $success = $this->manager->add($totpSecret);

        if (!$success) {
            throw new Exception("Could not save secret");
        }

        return new JsonResponse([
            'status' => 'success',
            'recovery_code' => $recoveryCode
        ]);
    }

    /**
     * Checks that the recovery code is correct
     * @return JsonResponse
     * @throws Exception
     *
     */
    public function verifyAndRecover(ServerRequest $request): JsonResponse
    {
        $body = $request->getParsedBody();

        $username = mb_strtolower($body['username']) ?? null;
        $password = $body['password'] ?? null;
        $recoveryCode = $body['recovery_code'] ?? null;

        if (!$username || !$password || !$recoveryCode) {
            throw new UserErrorException('Invalid parameter');
        }

        $matches = $this->manager->recover($username, $password, $recoveryCode);

        return new JsonResponse([
            'status' => 'success',
            'matches' => $matches
        ]);
    }

    /**
     * Remove secret if user has provided a valid code
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     * @throws UserErrorException
     */
    public function deleteSecret(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $body = $request->getParsedBody();
        $code = $body['code'] ?? '';

        if (!$code) {
            throw new Exception("Code must be provided");
        }

        // Get the user's secret
        $getQueryOpts = new TOTPSecretQueryOpts();
        $getQueryOpts->setUserGuid($user->getGuid());
        $totpSecret = $this->manager->get($getQueryOpts);

        if (!$totpSecret) {
            throw new Exception("Could not retrieve user secret");
        }

        // Validate code
        $codeIsValid = $this->twoFactor->verifyCode($totpSecret->getSecret(), $code, 1);

        if (!$codeIsValid) {
            throw new UserErrorException("Invalid code");
        }

        // Delete secret
        $opts = new TOTPSecretQueryOpts();
        $opts->setUserGuid($user->getGuid());

        $success =  $this->manager->delete($opts);

        if (!$success) {
            throw new Exception("Could not delete secret");
        }

        return new JsonResponse([
            'status' => 'success',
        ]);
    }
}
