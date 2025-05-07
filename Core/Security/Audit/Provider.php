<?php

namespace Minds\Core\Security\Audit;

use Minds\Common\IpAddress;
use Minds\Core\Analytics\PostHog\PostHogQueryService;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Security\Audit\Repositories\AuditRepository;
use Minds\Core\Security\Audit\Services\AuditService;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind(AuditService::class, fn (Di $di) =>
            new AuditService(
                auditRepository: $di->get(AuditRepository::class),
                activeSession: $di->get('Sessions\ActiveSession'),
                logger: $di->get('Logger'),
                ipAddress: new IpAddress(),
                postHogQueryService: $di->get(PostHogQueryService::class),
                config: $di->get(Config::class),
            ));

        $this->di->bind(AuditRepository::class, fn (Di $di) =>
            new AuditRepository(
                $di->get(Client::class),
                $di->get(Config::class),
                $di->get('Logger'),
            ));

        $this->di->bind(
            Controller::class,
            fn (Di $di) =>
            new Controller(
                $di->get(AuditService::class),
            )
        );
    }
}
