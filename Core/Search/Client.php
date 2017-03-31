<?php
/**
 * Search events listeners
 */
namespace Minds\Core\Search;

use Minds\Core\Config;

class Client extends \Elasticsearch\Client
{
    public function __construct(array $opts = [])
    {
        $hosts = Config::_()->elasticsearch_server ?: 'localhost';

        if (!is_array($hosts)) {
            $hosts = [ $hosts ];
        }

        $opts = array_merge([
            'hosts' => $hosts,
            'guzzleOptions' => [
                'command.request_options' => [
                    'connect_timeout' => 1, //1 second connect
                    'timeout' => 2 //2 second download
                 ]
             ]
        ], $opts);

        parent::__construct($opts);
    }
}
