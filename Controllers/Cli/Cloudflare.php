<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Core\Media\Video\CloudflareStreams\Webhooks;

class Cloudflare extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    public function exec()
    {
    }

    public function registerWebhook()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        /** @var Webhooks */
        $cloudflareStreamsWebhooks = Di::_()->get('Media\Video\CloudflareStreams\Webhooks');

        $secret = $cloudflareStreamsWebhooks->registerWebhook();

        $this->out('Your secret is ' . $secret . ' - Save this to settings.php');
    }
}
