<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\MultiTenant;

use Minds\Cli\Controller;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Services\TenantLifecyleService;
use Minds\Interfaces\CliControllerInterface;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class Lifecycle extends Controller implements CliControllerInterface
{
    private TenantLifecyleService $service;

    public function __construct()
    {
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
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $this->service->suspendExpiredTrials();
        $this->service->deleteSuspendedTenants();
    }


}
