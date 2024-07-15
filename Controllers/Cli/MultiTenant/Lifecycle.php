<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\MultiTenant;

use Minds\Cli\Controller;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Services\TenantLifecyleService;
use Minds\Interfaces\CliControllerInterface;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class Lifecycle extends Controller implements CliControllerInterface
{
    private TenantLifecyleService $service;

    public function __construct()
    {
        Di::_()->get(Config::class)->set('min_log_level', 'info');
        $this->service = Di::_()->get(TenantLifecyleService::class);
    }

    public function help($command = null)
    {
        // TODO: Implement help() method.
    }

    /**
     * @return void
     * @throws GraphQLException
     */
    public function exec(): void
    {
        $this->service->suspendExpiredTrials();
        $this->service->deleteSuspendedTenants();
    }


}
