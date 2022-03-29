<?php

/**
 * Minds SMS Service via Twilio
 */

namespace Minds\Core\SMS\Services;

use Minds\Common\IpAddress;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\SMS\Exceptions\InvalidPhoneException;
use Minds\Core\SMS\SMSServiceInterface;
use Twilio\Rest\Client as TwilioClient;
use Minds\Core\Security\RateLimits\KeyValueLimiter;

class Twilio implements SMSServiceInterface
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
     * Verifies the number isn't a voip line
     * @param $number
     * @return boolean
     * @throws InvalidPhoneException
     */
    public function verify($number): bool
    {
        try {
            $phone_number = $this->getClient()->lookups->v1->phoneNumbers($number)
                ->fetch(["type" => "carrier"]);

            if ($phone_number->carrier['error_code'] === 60601) {
                throw new InvalidPhoneException('Canadian phone numbers are currently not supported');
            }

            return $phone_number->carrier['type'] === 'mobile';
        } catch (InvalidPhoneException $e) {
            throw $e;
        } catch (\Exception $e) {
            error_log("[guard] Twilio error: {$e->getMessage()}");
            throw new InvalidPhoneException('Invalid Phone Number', 0, $e);
        }
    }

    /**
     * Send an sms
     */
    public function send($number, $message): bool
    {
        $result = null;

        // Only allow 5 messages sent to a number per day
        // To prevent malicious users flooding the system
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
            $result = $this->getClient()->messages->create(
                $number,
                [
                    'from' => $this->getConfig()['from'],
                    'body' => $message,
                ]
            );
        } catch (\Exception $e) {
            error_log("[guard] Twilio error: {$e->getMessage()}");
        }

        return $result ? $result->sid : false;
    }

    /**
     * Get the twilio client
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
     * Get the twilio config
     * @return array
     */
    private function getConfig(): array
    {
        return $this->config->get('twilio');
    }
}
