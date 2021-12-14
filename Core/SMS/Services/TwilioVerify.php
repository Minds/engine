<?php

/**
 * Minds Twilio Verify Service
 */

namespace Minds\Core\SMS\Services;

use Minds\Common\IpAddress;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\SMS\Exceptions\InvalidPhoneException;
use Minds\Core\SMS\SMSServiceInterface;
use Twilio\Rest\Client as TwilioClient;
use Minds\Core\Security\RateLimits\KeyValueLimiter;

class TwilioVerify implements SMSServiceInterface
{
    /** @var TwilioClient */
    protected $client;

    /** @var Config */
    protected $config;

    /** @var string */
    protected $from;

    /** @var KeyValueLimiter */
    protected $kvLimiter;

    /** @var IpAddress */
    protected $ipAddress;

    public function __construct($client = null, $config = null, $kvLimiter = null, IpAddress $ipAddress = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->client = $client;
        $this->kvLimiter = $kvLimiter ?? Di::_()->get("Security\RateLimits\KeyValueLimiter");
        $this->ipAddress = $ipAddress ?? new IpAddress();
    }

    /**
     * Verifies the number isn't a voip line.
     * @param $number
     * @return boolean
     * @throws InvalidPhoneException
     */
    public function verify($number): bool
    {
        try {
            $phone_number = $this->getClient()->lookups->v1->phoneNumbers($number)
                ->fetch(["type" => "carrier"]);

            return $phone_number->carrier['type'] !== 'voip';
        } catch (\Exception $e) {
            error_log("[guard] Twilio error: {$e->getMessage()}");
            throw new InvalidPhoneException('Invalid Phone Number', 0, $e);
        }
    }

    /**
     * Send a verification request.
     * @param string $number - users phone number.
     * @param string $number - depreciated.
     */
    public function send($number, $message = ''): bool
    {
        $result = null;

        // // Only allow 5 messages sent to a number per day
        // // To prevent malicious users flooding the system
        $phoneNumberHash = hash('sha256', $number . $this->config->get('phone_number_hash_salt'));

        $this->kvLimiter
            ->setKey('sms-sender-twilio')
            ->setValue($phoneNumberHash)
            ->setSeconds(86400) // Day
            ->setMax(5) // 5 per day
            ->checkAndIncrement(); // Will throw exception

        // Only allow 10 SMS messages per IP address per day
        $this->kvLimiter
            ->setKey('sms-sender-twilio-ip')
            ->setValue($this->ipAddress->get())
            ->setSeconds(86400) // Day
            ->setMax(10) // 10 per day
            ->checkAndIncrement(); // Will throw exception

        try {
            // Send SMS
            $result = $this->getClient()->verify->v2->services($this->getConfig()['verify']['service_sid'])
                ->verifications
                ->create($number, "sms");
        } catch (\Exception $e) {
            error_log("[guard] TwilioVerify error: {$e->getMessage()}");
        }

        return $result ? $result->sid : false;
    }

    /**
     * Verify a given code.
     * @param string $code - code received by user.
     * @param string $number - users phone number.
     * @return bool - true if code is valid.
     */
    public function verifyCode(string $code, string $number): bool
    {
        $result = $this->getClient()->verify->v2->services(
            $this->getConfig()['verify']['service_sid']
        )
            ->verificationChecks
            ->create(
                $code,
                ["to" => $number]
            );
        
        return $result->status === 'approved';
    }

    /**
     * Get the Twilio client
     * @return TwilioClient
     */
    private function getClient(): TwilioClient
    {
        if (!$this->client) {
            $AccountSid = $this->getConfig()['account_sid'] ?: 'not set';
            $AuthToken = $this->getConfig()['auth_token'] ?: 'not set';
            $this->client = new TwilioClient($AccountSid, $AuthToken);
        }
        return $this->client;
    }

    /**
     * Get Twilio config
     * @return array
     */
    private function getConfig(): array
    {
        return $this->config->get('twilio');
    }
}
