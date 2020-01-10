<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Events\Dispatcher;
use Minds\Interfaces;

class Notification extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        switch ($command) {
            case 'send':
                $this->out('Send a notification');
                $this->out('--namespace=<type> Notification namespace');
                $this->out('--to=<user guid> User to send notification to');
                $this->out('--from=<entity guid> Entity notification is from (defaults to system user)');
                $this->out('--view=<view> Notification view');
                $this->out('--params=<params> JSON payload data');
            // no break
            default:
                $this->out('Syntax usage: cli notification <cmd>');
                $this->displayCommandHelp();
        }
    }

    public function exec()
    {
        $this->help();
    }

    public function send()
    {
        $namespace = $this->getOpt('namespace');
        $to = $this->getOpt('to');
        $from = $this->getOpt('from') ?? \Minds\Core\Notification\Notification::SYSTEM_ENTITY;
        $view = $this->getOpt('view');
        $params = $this->getOpt('params') ?? '{}';

        if (is_null($namespace)) {
            $this->out('namespace must be set');
            return;
        }

        if (is_null($to)) {
            $this->out('to must be set');
            return;
        }

        if (is_null($view)) {
            $this->out('view must be set');
            return;
        }

        $paramsDecoded = json_decode($params, true);
        if (is_null($paramsDecoded)) {
            $this->out('Params is not valid JSON');
            return;
        }

        $eventParams = [
            'to' => [$to],
            'from' => $from,
            'notification_view' => $view,
            'params' => $paramsDecoded
        ];

        $sent = Dispatcher::trigger('notification', $namespace, $eventParams);

        if ($sent) {
            $this->out('Notification sent');
        } else {
            $this->out('Error sending notification - is from guid valid?');
        }
    }
}
