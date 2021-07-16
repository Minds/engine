<?php
namespace Minds\Core\Security\Password\PwnedPasswords;

use GuzzleHttp;
use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

class Client
{
    /** @var GuzzleHttp\Client */
    protected $httpClient;

    /**
     * @param GuzzleHttp\Client $httpClient
     */
    public function __construct($httpClient = null)
    {
        $this->httpClient = $httpClient ?? new GuzzleHttp\Client();
    }

    /**
     * @param string $hashPrefix
     * @return string of rows
     * @throws Exception
     */
    public function getRows(string $hashPrefix): string
    {
        if (strlen($hashPrefix) !== 5) {
            throw new Exception("Hash prefix must be 5 chars in length");
        }

        $endpoint = "https://api.pwnedpasswords.com/range/" . $hashPrefix;

        try {
            $response = $this->httpClient->request('GET', $endpoint, []);

            $rows = (string) $response->getBody();
            return $rows;
        } catch (ClientException $e) {
            return '';
        } catch (RequestException $e) {
            return '';
        } catch (\Exception $e) {
            return '';
        };
    }
}
