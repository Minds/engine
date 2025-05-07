<?php

declare(strict_types=1);

namespace Minds\Core\Authentication\PersonalApiKeys;

use Minds\Core\Authentication\PersonalApiKeys\Controllers\PersonalApiKeyController;
use Minds\Core\Authentication\PersonalApiKeys\Repositories\PersonalApiKeyRepository;
use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyAuthService;
use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyHashingService;
use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyManagementService;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Security\Audit\Services\AuditService;

class Provider extends DiProvider
{
    public function register(): void
    {
        /**
         * Controllers
         */
        $this->di->bind(PersonalApiKeyController::class, fn (Di $di) => new PersonalApiKeyController(
            managementService: $di->get(PersonalApiKeyManagementService::class),
        ));

        /**
         * Services
         */
        $this->di->bind(PersonalApiKeyManagementService::class, fn (Di $di) => new PersonalApiKeyManagementService(
            repository: $di->get(PersonalApiKeyRepository::class),
            hashingService: $di->get(PersonalApiKeyHashingService::class),
            auditService: $di->get(AuditService::class),
        ));
        $this->di->bind(PersonalApiKeyAuthService::class, fn (Di $di) => new PersonalApiKeyAuthService(
            repository: $di->get(PersonalApiKeyRepository::class),
            hashingService: $di->get(PersonalApiKeyHashingService::class),
        ));
        $this->di->bind(PersonalApiKeyHashingService::class, fn (Di $di) => new PersonalApiKeyHashingService(
            config: $di->get(Config::class),
        ));

        /**
         * Repositories
         */
        $this->di->bind(PersonalApiKeyRepository::class, fn (Di $di) => new PersonalApiKeyRepository(
            mysqlHandler: $di->get(Client::class),
            config: $di->get(Config::class),
            logger: $di->get('Logger'),
        ));
    }
}
