<?php

namespace Minds\Controllers\Cli;

use Elasticsearch\Common\Exceptions\ServerErrorResponseException;
use Minds\Cli;
use Minds\Core;
use Minds\Entities;
use Minds\Interfaces;

class Config extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        $this->out('Prints current config for the value');
        $this->out('--key={config key}');
        $this->displayCommandHelp();
    }

    public function exec()
    {
        $config = Core\Di\Di::_()->get('Config');
        $key = $this->getOpt('key');
        $this->out("{$key}:");
        $this->out(var_export($config->get($key), true));
    }
}
