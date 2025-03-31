<?php
declare(strict_types=1);

namespace Minds\Integrations\Seco;

use GuzzleHttp\Client;
use Minds\Core\Ai\Ollama\OllamaClient;
use Minds\Core\Authentication\Services\RegisterService;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;
use Minds\Core\EntitiesBuilder;
use Minds\Integrations\Seco\Controllers\AIProxyContoller;
use Minds\Integrations\Seco\Controllers\ImportThreadsController;
use Minds\Integrations\Seco\Services\ImportThreadsService;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            ImportThreadsService::class,
            fn (Di $di): ImportThreadsService => new ImportThreadsService(
                entitiesBuilder: $di->get(EntitiesBuilder::class),
                save: new Save(),
                registerService: $di->get(RegisterService::class),
                commentManager: $di->get('Comments\Manager'),
                groupMembershipManager: $di->get(GroupMembershipManager::class),
                acl: $di->get('Security\ACL'),
            )
        );

        $this->di->bind(
            ImportThreadsController::class,
            fn (Di $di): ImportThreadsController => new ImportThreadsController(
                importThreadsService: $di->get(ImportThreadsService::class),
                entitiesBuilder: $di->get(EntitiesBuilder::class),
            )
        );

        $this->di->bind(
            AIProxyContoller::class,
            fn (Di $di): AIProxyContoller => new AIProxyContoller(
                ollamaClient: $di->get(OllamaClient::class),
                entitiesBuilder: $di->get(EntitiesBuilder::class)
            )
        );
    }
}
