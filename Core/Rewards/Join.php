<?php
/**
 * Join the rewards program
 */

namespace Minds\Core\Rewards;

use Minds\Core\Di\Di;
use Minds\Core;
use Minds\Core\Referrals\Referral;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Core\SMS\Exceptions\VoIpPhoneException;
use Minds\Entities\User;
use Minds\Core\Util\BigNumber;
use Minds\Exceptions\UserErrorException;

class Join
{
    /** @var TwoFactor $twofactor */
    private $twofactor;

    /** @var Core\SMS\SMSServiceInterface $sms */
    private $sms;

    /** @var PhoneNumberUtil $libphonenumber */
    private $libphonenumber;

    /** @var User $user */
    private $user;

    /** @var int $number */
    private $number;

    /** @var int $code */
    private $code;

    /** @var string $secret */
    private $secret;

    /** @var Config $config */
    private $config;

    /** @var ReferralValidator */
    private $validator;

    /** @var OfacBlacklist */
    private $ofacBlacklist;

    /** @var TestnetBalance */
    private $testnetBalance;

    /** @var Call */
    private $db;

    /** @var ReferralDelegate $eventsDelegate */
    private $referralDelegate;

    /** @var KeyValueLimiter */
    protected $kvLimiter;

    /** @var JoinedValidator */
    private $joinedValidator;

    /** @var Features\Manager */
    private $featuresManager;

    /** @var TwilioVerify */
    private $twilioVerify;

    public function __construct(
        $twofactor = null,
        $sms = null,
        $libphonenumber = null,
        $config = null,
        $validator = null,
        $db = null,
        $joinedValidator = null,
        $ofacBlacklist = null,
        $testnetBalance = null,
        $referralDelegate = null,
        KeyValueLimiter  $kvLimiter = null,
        $featuresManager = null,
        $twilioVerify = null
    ) {
        $this->twofactor = $twofactor ?: Di::_()->get('Security\TwoFactor');
        $this->sms = $sms ?: Di::_()->get('SMS');
        $this->libphonenumber = $libphonenumber ?: \libphonenumber\PhoneNumberUtil::getInstance();
        $this->config = $config ?: Di::_()->get('Config');
        $this->validator = $validator ?: Di::_()->get('Rewards\ReferralValidator');
        $this->db = $db ?: new Core\Data\Call('entities_by_time');
        $this->joinedValidator = $joinedValidator ?: Di::_()->get('Rewards\JoinedValidator');
        $this->ofacBlacklist = $ofacBlacklist ?: Di::_()->get('Rewards\OfacBlacklist');
        $this->testnetBalance = $testnetBalance ?: Di::_()->get('Blockchain\Wallets\OffChain\TestnetBalance');
        $this->referralDelegate = $referralDelegate ?: new Delegates\ReferralDelegate;
        $this->kvLimiter = $kvLimiter ?? Di::_()->get("Security\RateLimits\KeyValueLimiter");
        $this->featuresManager = $featuresManager ?? Di::_()->get('Features\Manager');
        $this->twilioVerify = $twilioVerify ?? Di::_()->get('SMS\Twilio\Verify');
    }

    public function setUser(&$user)
    {
        $this->user = $user;
        return $this;
    }

    public function setNumber($number)
    {
        if ($this->ofacBlacklist->isBlacklisted($number)) {
            throw new \Exception('Because your country is currently listed on the OFAC sanctions list you are unable to earn rewards or purchase tokens');
        }
        $proto = $this->libphonenumber->parse("+$number");
        $this->number = $this->libphonenumber->format($proto, \libphonenumber\PhoneNumberFormat::E164);

        if (md5($this->number) === 'cd6fd474ebbc6f5322d4267a85648ebe') {
            error_log("Bad user found: {$this->user->username}");
            throw new \Exception("Stop.");
        }
        return $this;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * @return string
     * @throws VoIpPhoneException
     */
    public function verify()
    {

        // Limit a single account to 3 attempts per day
        $this->kvLimiter
            ->setKey('rewards-verify')
            ->setValue($this->user->getGuid())
            ->setSeconds(86400) // Day
            ->setMax(3) // 2 per day
            ->checkAndIncrement(); // Will throw exception


        if ($this->featuresManager->has('twilio-verify')) {
            if (!$this->twilioVerify->verify($this->number)) {
                throw new VoIpPhoneException();
            }

            if (!$this->user->isEmailConfirmed()) {
                throw new UnverifiedEmailException();
            }

            $this->twilioVerify->send($this->number, '');
            return;
        }

        $secret = $this->twofactor->createSecret();
        $code = $this->twofactor->getCode($secret);

        $user_guid = $this->user->guid;
        $this->db->insert("rewards:verificationcode:$user_guid", compact('code', 'secret'));

        if (!$this->sms->verify($this->number)) {
            throw new VoIpPhoneException();
        }

        if (!$this->user->isTrusted()) {
            throw new UnverifiedEmailException();
        }

        $username = $this->user->getUsername();

        $this->sms->send($this->number, "Minds Rewards Code for @$username: $code");

        return $secret;
    }

    public function resendCode()
    {
        if ($this->featuresManager->has('twilio-verify')) {
            $this->verify();
            return;
        }

        $user_guid = $this->user->guid;
        $username = $this->user->getUsername();
        $row = $this->db->getRow("rewards:verificationcode:$user_guid");

        if (!empty($row)) {
            if (!$this->sms->verify($this->number)) {
                throw new VoIpPhoneException();
            }

            $code = $row['code'];
            $this->sms->send($this->number, "Minds Rewards Code for @$username: $code");

            return $row['secret'];
        }
    }

    public function confirm()
    {
        if ($this->user->getPhoneNumberHash()) {
            return false; //already joined
        }

        $valid = false;
        
        if ($this->featuresManager->has('twilio-verify')) {
            $valid = $this->twilioVerify->verifyCode($this->code, $this->number);
        } else {
            $valid = $this->twofactor->verifyCode($this->secret, $this->code, 8);
        }
        
        if ($valid) {
            $hash = hash('sha256', $this->number . $this->config->get('phone_number_hash_salt'));
            $this->user->setPhoneNumberHash($hash);
            $this->user->save();

            $this->joinedValidator->setHash($hash);
            if ($this->joinedValidator->validate()) {
                $event = new Core\Analytics\Metrics\Event();
                $event->setType('action')
                    ->setProduct('platform')
                    ->setUserGuid((string) $this->user->guid)
                    ->setUserPhoneNumberHash($hash)
                    ->setAction('joined')
                    ->push();
            }

            // Validate referral and give both prospect and referrer +50 contribution score
            if ($this->user->referrer && $this->user->guid != $this->user->referrer) {
                $this->validator->setHash($hash);

                if ($this->validator->validate()) {
                    $event = new Core\Analytics\Metrics\Event();
                    $event->setType('action')
                        ->setProduct('platform')
                        ->setUserGuid((string) $this->user->guid)
                        ->setUserPhoneNumberHash($hash)
                        ->setEntityGuid((string) $this->user->referrer)
                        ->setEntityType('user')
                        ->setAction('referral')
                        ->push();

                    $this->referralDelegate->onReferral($this->user);
                } else {
                    $this->referralDelegate->onReject($this->user->guid, $this->user->referrer);
                }
            }
        } else {
            throw new \Exception('The confirmation failed');
        }

        return true;
    }
}
