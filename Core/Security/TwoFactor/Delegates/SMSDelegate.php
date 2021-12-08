<?php
/**
 * TwoFactor SMS Delegate
 */
namespace Minds\Core\Security\TwoFactor\Delegates;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager;
use Minds\Core\Log;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\TwoFactor as TwoFactorService;
use Minds\Core\Security\TwoFactor\TwoFactorInvalidCodeException;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Core\SMS;
use Minds\Core\SMS\Services\TwilioVerify;
use Minds\Entities\User;
use Minds\Helpers\FormatPhoneNumber;
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

    /** @var Features\Manager */
    protected $featuresManager;

    /** @var TwilioVerify */
    protected $twilioVerify;

    public function __construct(
        TwoFactorService $twoFactorService = null,
        $smsService = null,
        PsrWrapper $cache = null,
        Manager $featuresManager = null,
        TwilioVerify $twilioVerify = null
    ) {
        $this->twoFactorService = $twoFactorService ?? new TwoFactorService();
        $this->smsService = $smsService ?? Di::_()->get('SMS');
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->featuresManager = $featuresManager ?? Di::_()->get('Features\Manager');
        $this->twilioVerify = $twilioVerify ?? Di::_()->get('SMS\Twilio\Verify');
    }

    /**
     * Should trigger the 2fa process
     * @param User $user
     * @throws TwoFactorRequiredException
     */
    public function onRequireTwoFactor(User $user): void
    {
        if ($this->featuresManager->has('twilio-verify')) {
            $number = FormatPhoneNumber::format($user->telno);
            $this->twilioVerify->send($number, '');
        } else {
            $secret = $this->twoFactorService->createSecret(); //we have a new secret for each request

            $this->logger->info('2fa - sending SMS to ' . $user->getGuid());

            $message = "Minds (@{$user->getUsername()}) 2FA code: " . $this->twoFactorService->getCode($secret);

            // Apply + to all number except 10 digit US numbers
            // NOTE: This is a hacky workaround
            if ($user->telno[0] !== '+' && strlen($user->telno) !== 10) {
                $user->telno = '+'.$user->telno;
            }

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
            @header("X-MINDS-SMS-2FA-KEY: $key", true);
        }

        //forward to the twofactor page
        throw new TwoFactorRequiredException();
    }

    /**
     * Called upon authentication when the twofactor code has been provided
     * @param User $user
     * @param int $code
     * @return void
     */
    public function onAuthenticateTwoFactor(User $user, string $code): void
    {
        if ($this->featuresManager->has('twilio-verify')) {
            $number = FormatPhoneNumber::format($user->telno);

            if (!$this->twilioVerify->verifyCode($code, $number)) {
                throw new TwoFactorInvalidCodeException();
            }
        } else {
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
                && $user->getGuid() === (string) $payload['_guid']
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
}
