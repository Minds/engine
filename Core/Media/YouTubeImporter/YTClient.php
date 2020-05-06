<?php
namespace Minds\Core\Media\YouTubeImporter;

use Minds\Core\Di\Di;
use Google_Client;
use Google_Service_YouTube;

class YTClient
{
    /** @var Google_Client */
    protected $client;

    /** @var Config */
    protected $config;

    public function __construct($client = null, $config = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->client = $client ?? new Google_Client();
        $this->client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);
        $this->client->setRedirectUri($this->config->get('site_url')
            . 'api/v3/media/youtube-importer/account/redirect');
        $this->client->setAccessType('offline');
    }

    /**
     * Configures the Google Client to either use a developer key or a client id / secret
     * @param $useDevKey
     * @return Google_Client
     */
    public function getClient($useDevKey): Google_Client
    {
        // set auth config
        if ($useDevKey) {
            $this->client->setDeveloperKey($this->config->get('google')['youtube']['api_key']);
            $this->client->setClientId('');
            $this->client->setClientSecret('');
        } else {
            $this->client->setDeveloperKey('');
            $this->client->setClientId($this->config->get('google')['youtube']['client_id']);
            $this->client->setClientSecret($this->config->get('google')['youtube']['client_secret']);
        }

        return $this->client;
    }

    /**
     * Returns youtube service
     * @param bool $useDevKey
     * @return Google_Service_YouTube
     */
    public function getService($useDevKey): Google_Service_YouTube
    {
        return new Google_Service_YouTube($this->getClient($useDevKey));
    }
}
