<?php
/**
 * Minds EventStreams Provider.
 */

namespace Minds\Core\EventStreams;

use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EventStreams\Topics\ChatNotificationsTopic;
use Minds\Core\EventStreams\Topics\TenantBootstrapRequestsTopic;
use Minds\Core\EventStreams\Topics\ViewsTopic;
use Pulsar;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(\Pulsar\Client::class, function ($di) {
            $config = $di->get('Config');

            $pulsarClient = new Pulsar\Client();

            $pulsarConfig = $config->get('pulsar');
            $pulsarHost = $pulsarConfig['host'] ?? 'pulsar';
            $pulsarPort = $pulsarConfig['port'] ?? 6650;
            $pulsarSchema = ($pulsarConfig['ssl'] ?? true) ? 'pulsar+ssl' : 'pulsar';

            $clientConfig = new Pulsar\ClientConfiguration();
            $clientConfig->setLogLevel(E_ERROR);

            if ($pulsarConfig['ssl'] ?? true) {
                $clientConfig->setUseTls(true)
                    ->setTlsAllowInsecureConnection($pulsarConfig['ssl_skip_verify'] ?? false)
                    ->setTlsTrustCertsFilePath($pulsarConfig['ssl_cert_path'] ?? '/var/secure/pulsar.crt');
            }

            $pulsarClient = new Pulsar\Client();
            $pulsarClient->init("$pulsarSchema://$pulsarHost:$pulsarPort", $clientConfig);

            // Close the connection when php closes
            register_shutdown_function(function () use ($pulsarClient) {
                $pulsarClient?->close();
            });

            return $pulsarClient;
        }, ['useFactory' => true]);

        $this->di->bind('EventStreams\Topics\ActionEventsTopic', function ($di) {
            return new Topics\ActionEventsTopic();
        }, ['useFactory' => false]);

        $this->di->bind(ViewsTopic::class, function ($di): ViewsTopic {
            return new ViewsTopic();
        }, ['useFactory' => false]);

        $this->di->bind(
            ChatNotificationsTopic::class,
            fn (Di $di): ChatNotificationsTopic => new ChatNotificationsTopic()
        );

        $this->di->bind(
            TenantBootstrapRequestsTopic::class,
            fn (Di $di): TenantBootstrapRequestsTopic => new TenantBootstrapRequestsTopic()
        );
    }
}
