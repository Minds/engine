<?php
namespace Minds\Core\Email\Verify\Services;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Log\Logger;

class TheChecker
{
    /** @var Http $http */
    private $http;

    /** @var Config $config */
    private $config;

    /** @var Logger $config */
    private $logger;

    /** Whitelisted domains */
    private $whitelist = [
        '@icloud\.com',
        '@me\.com',
        '@mac\.com',
        '@comporium\.net,'
    ];

    public function __construct(
        $http = null,
        $config = null,
        Logger $logger = null
    ) {
        $this->config = $config ?: Config::_();
        $this->http = $http ?: Di::_()->get('Http');
        $this->logger = $logger ?: Di::_()->get('Logger');
    }

    /**
     * Verify if an email is valid
     * @param string $email
     * @return bool
     */
    public function verify($email)
    {
        if (!$this->config->get('thechecker_secret')) {
            $this->logger->error('No TheChecker secret set for email validity check.');
            return true;
        }

        if ($this->isWhitelisted($email)) {
            return true;
        }

        try {
            $content = $this->http->get('https://api.thechecker.co/v2/verify?email=' . $email . '&api_key=' . $this->config->get('thechecker_secret'), [
                'curl' => [
                    CURLOPT_FOLLOWLOCATION => 1,
                    CURLOPT_NOSIGNAL => 1,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_CONNECTTIMEOUT_MS => 3 * 1000,
                    CURLOPT_TIMEOUT_MS => 10 * 1000,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return true; // If provider errors out then verify
        }

        $response = json_decode($content, true);

        if ($response['message']) {
            // e.g. "You've provided an invalid api key.".
            $this->logger->error($response['message']);
        }

        if ($response['result'] !== 'deliverable') {
            $this->logger->info(
                'TheChecker | not-deliverable'
                .', result: '.$response['result']
                .', for email: '.$response['email']
                .', with reason: '.$response['reason']
            );
        }

        return !($response['result'] == 'undeliverable');
    }

    /**
     * Returns whether the email address is whitelisted.
     *
     * @param string $email - the email address to check
     * @return boolean true if email is whitelisted.
     */
    public function isWhitelisted($email): bool
    {
        foreach ($this->whitelist as $whitelisted) {
            if (preg_match("/".$whitelisted."$/", $email)) {
                return true;
            }
        }
        return false;
    }
}
