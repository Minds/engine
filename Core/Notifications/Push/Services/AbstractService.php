<?php
namespace Minds\Core\Notifications\Push\Services;

use GuzzleHttp;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

abstract class AbstractService
{
    /** @var GuzzleHttp\Client  */
    protected $client;

    /** @var Config */
    protected $config;

    public function __construct(GuzzleHttp\Client $client = null, Config $config = null)
    {
        $this->client = $client ?? new GuzzleHttp\Client();
        $this->config = $config ?? Di::_()->get('Config');
    }
}
