<?php
/**
 * Minds Permaweb Manager, interfaces with our gateway.
 * @author Ben Hayward
 */
namespace Minds\Core\Permaweb;

use Minds\Core\Di\Di;

class Manager
{
    public function __construct($http = null, $config = null, $logger = null)
    {
        $this->http = $http ?: Di::_()->get('Http');
        $this->config = $config ?: Di::_()->get('Config');
        $this->logger = $logger ?: Di::_()->get('Logger');
    }

    /**
     * Get transaction data by ID
     *
     * @param string $id - transaction id
     * @return array - response from gateway
     */
    public function getById(string $id): array
    {
        try {
            $baseUrl = $this->buildUrl($this->config->get('arweave'));
            $response = $this->http->get($baseUrl.'permaweb/'.$id);
            return (array) json_decode($response);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Gets newsfeed url for a post.
     * @param { string } guid of post.
     * @return string - url.
     */
    public function getMindsUrl(string $guid): string
    {
        return $this->config->get('site_url').'newsfeed/'.$guid;
    }

    /**
     * Save to permaweb
     * @param $opts - guid, minds link required, thumbnail_src optional
     * @return array response from microservice
     */
    public function save(array $opts): array
    {
        try {
            if (!$opts['guid'] || !$opts['minds_link']) {
                throw new \Exception('You must pass all required parameters to save to the permaweb');
            }

            $baseUrl = $this->buildUrl($this->config->get('arweave'));
            $response = $this->http->post($baseUrl.'permaweb/', $opts, [
                'headers' => [
                    'Content-Type: application/x-www-form-urlencoded',
                ]
            ]);

            return (array) json_decode($response);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Gets ID by signing tx inside service container and passing the id back.
     *
     * @param $opts - guid, minds link required, thumbnail_src optional
     * @return string id of the tx.
     */
    public function generateId($opts): string
    {
        try {
            if (!$opts['guid'] || !$opts['minds_link']) {
                throw new \Exception('You must pass all required parameters to save to the permaweb');
            }

            $baseUrl = $this->buildUrl($this->config->get('arweave'));
            $response = json_decode($this->http->post($baseUrl.'permaweb/getId/', $opts, [
                'headers' => [
                    'Content-Type: application/x-www-form-urlencoded',
                ]
            ]));

            if ($response->status !== 200) {
                throw new \Exception('An unknown error has occurred generating permaweb id. Check permaweb containers output.');
            }

            return $response->id;
        } catch (\Exception $e) {
            $this->logger->error($e);
            return '';
        }
    }

    private function buildUrl($arweaveConfig): string
    {
        return 'http://'.$arweaveConfig['host'].':'.$arweaveConfig['port'].'/';
    }
}
