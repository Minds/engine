<?php

namespace Minds\Core\Http\Curl;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Traits\MagicAttributes;

class Client
{
    use MagicAttributes;


    public function __construct(private ?CurlWrapper $curl = null, protected ?Config $config = null)
    {
        $this->curl = $curl ?: new CurlWrapper();
        $this->config ??= Di::_()->get('Config');
    }

    public function get($url, array $options = [])
    {
        $options = array_merge(['method' => 'get', 'url' => $url], $options);

        return $this->request($options);
    }

    public function post($url, array $data = [], array $options = [])
    {
        $options = array_merge(['method' => 'post', 'url' => $url, 'data' => $data], $options);

        return $this->request($options);
    }

    public function put($url, array $data = [], array $options = [])
    {
        $options = array_merge(['method' => 'put', 'url' => $url, 'data' => $data], $options);

        return $this->request($options);
    }

    public function delete($url, array $data = [], array $options = [])
    {
        $options = array_merge(['method' => 'delete', 'url' => $url, 'data' => $data], $options);

        return $this->request($options);
    }

    public function request($options)
    {
        $options = array_merge([
            'method' => 'get',
            'url' => '',
            'data' => [],
            'headers' => [],
            'curl' => [],
            'limit' => 0,
            'useHttpProxy' => true
        ], $options);

        $headers = [];

        if (!$options['url']) {
            return false;
        }

        $this->curl->setOpt(CURLOPT_URL, $options['url']);
        $this->curl->setOpt(CURLOPT_RETURNTRANSFER, 1);

        $validMethods = ['get', 'post', 'put', 'delete', 'options', 'head'];

        if (
            in_array('Content-Type: application/x-www-form-urlencoded', $options['headers'], true) &&
            is_array($options['data'])
        ) {
            $options['data'] = http_build_query($options['data']);
        } elseif (
            in_array('Content-Type: application/json', $options['headers'], true) &&
            is_array($options['data'])
        ) {
            $options['data'] = json_encode($options['data']);
        }

        if ($options['limit']) {
            $this->curl->setLimit($options['limit']);
        }

        if (in_array($options['method'], $validMethods, true)) {
            switch ($options['method']) {
                case 'get':
                    $this->curl->setOpt(CURLOPT_HTTPGET, true);
                    break;
                case 'post':
                    $this->curl->setOpt(CURLOPT_POST, true);
                    $this->curl->setOpt(CURLOPT_POSTFIELDS, $options['data']);
                    break;
                case 'options':
                case 'head':
                    $this->curl->setOpt(CURLOPT_CUSTOMREQUEST, strtoupper($options['method']));
                    break;
                case 'put':
                case 'delete':
                    $this->curl->setOpt(CURLOPT_CUSTOMREQUEST, strtoupper($options['method']));
                    $headers = array_merge($headers, ['X-HTTP-Method-Override: '.strtoupper($options['method'])]);
                    $this->curl->setOpt(CURLOPT_POSTFIELDS, $options['data']);
                    break;
            }
        }

        if ($options['headers']) {
            $headers = array_merge($headers, $options['headers']);
        }

        $this->curl->setOpt(CURLOPT_HTTPHEADER, $headers);
        
        if (($httpProxy = $this->config->get('http_proxy')) && $options['useHttpProxy'] === true) {
            $this->curl->setOpt(CURLOPT_PROXY, $httpProxy);
        }

        if ($options['curl']) {
            $this->curl->setOptArray($options['curl']);
        }

        $response = $this->curl->execute();
        $errorNumber = $this->curl->getErrorNumber();
        $error = $this->curl->getError();

        if ($errorNumber) {
            if ($errorNumber === CURLE_ABORTED_BY_CALLBACK) {
                throw new \Exception("Exceeded max download size of {$options['limit']} Kbs", $errorNumber);
            }
            throw new \Exception($error, $errorNumber);
        }

        return $response;
    }
}
