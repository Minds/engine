<?php

/**
 * Minds SMS Service via Twilio
 */

namespace Minds\Core\SMS\Services;

use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\SMS\Exceptions\InvalidPhoneException;
use Minds\Core\SMS\SMSServiceInterface;
use Twilio\Rest\Client as TwilioClient;

class Twilio implements SMSServiceInterface
{
    /** @var TwilioClient */
    protected $client;

    /** @var Config */
    protected $config;

    /** @var string */
    protected $from;

    public function __construct($client = null, $config = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->client = $client;
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

            return $phone_number->carrier['type'] !== 'voip';
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
