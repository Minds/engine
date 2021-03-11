<?php
/**
 * TwoFactor SMS Delegate
 */
namespace Minds\Core\Security\TwoFactor\Delegates;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Log;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\TwoFactor as TwoFactorService;
use Minds\Core\Security\TwoFactor\TwoFactorInvalidCodeException;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Core\SMS;
use Minds\Entities\User;
use Zend\Diactoros\ServerRequestFactory;

class SMSDelegate implements TwoFactorDelegateInterface
{
    /** @var TwoFactorService */
    protected $twoFactorService;

    /** @var SMS\Services\Twilio */
    protected $smsService;

    /** @var PsrWrapper */
    protected $cache;

    /** @var Log\Logger */
    protected $logger;

    public function __construct(TwoFactorService $twoFactorService = null, $smsService = null, PsrWrapper $cache = null)
    {
        $this->twoFactorService = $twoFactorService ?? new TwoFactorService();
        $this->smsService = $smsService ?? Di::_()->get('SMS');
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
        $this->logger = $logger ?? Di::_()->get('Logger');
    }
    /**
     * Should trigger the 2fa process
     * @param User $user
     * @throws TwoFactorRequiredException
     */
    public function onRequireTwoFactor(User $user): void
    {
        $secret = $this->twoFactorService->createSecret(); //we have a new secret for each request

        $this->logger->info('2fa - sending SMS to ' . $user->getGuid());

        $message = 'Minds verification code: '. $this->twoFactorService->getCode($secret);
        
        $this->smsService->send($user->telno, $message);

        // create a lookup of a random key. The user can then use this key along side their twofactor code
        // to login. This temporary code should be removed within 2 minutes.
        $bytes = openssl_random_pseudo_bytes(128);
        $key = hash('sha512', $user->username . $user->salt . $bytes);

        $this->cache->set($key, json_encode([
            '_guid' => $user->guid,
            'ts' => time(),
            'secret' => $secret
        ]), 300); // Expire after 5 mins

        // Set a header with the 2fa request id
        header("X-MINDS-SMS-2FA-KEY: $key", true);

        //forward to the twofactor page
        throw new TwoFactorRequiredException();
    }

    /**
     * Called upon authentication when the twofactor code has been provided
     * @param User $user
     * @param int $code
     * @return void
     */
    public function onAuthenticateTwoFactor(User $user, int $code): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $key = $request->getHeader('X-MINDS-SMS-2FA-KEY')[0];
        $payload = $this->cache->get($key);

        if (!$payload) {
            throw new TwoFactorInvalidCodeException();
        }

        $payload = json_decode($payload, true);

        // we allow for 120 seconds (2 mins) after we send a code
        if ($payload['_guid']
            && $payload['ts'] > time() - 120
            && $user->getGuid() === $payload['_guid']
        ) {
            $secret = $payload['secret'];
        } else {
            throw new TwoFactorInvalidCodeException();
        }

        if (!$this->twoFactorService->verifyCode($secret, $code, 1)) {
            throw new TwoFactorInvalidCodeException();
        }
    }
}
