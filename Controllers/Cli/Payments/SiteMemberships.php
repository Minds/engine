<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\Payments;

use Minds\Cli\Controller;
use Minds\Core\Di\Di;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipsRenewalsService;
use Minds\Exceptions\ServerErrorException;
use Minds\Interfaces\CliControllerInterface;
use Stripe\Exception\ApiErrorException;

class SiteMemberships extends Controller implements CliControllerInterface
{
    public function help($command = null)
    {
        $this->out('Syntax usage: payments sitememberships [command]');
    }

    public function exec()
    {
        $this->help();
    }

    /**
     * @return void
     * @throws ServerErrorException
     * @throws ApiErrorException
     */
    public function sync(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        /**
         * @var SiteMembershipsRenewalsService $siteMembershipsRenewalsService
         */
        $siteMembershipsRenewalsService = Di::_()->get(SiteMembershipsRenewalsService::class);

        $siteMembershipsRenewalsService->syncSiteMemberships();
    }
}
