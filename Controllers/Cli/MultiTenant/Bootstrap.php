<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\MultiTenant;

use Minds\Cli\Controller;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\Events\TenantBootstrapRequestEvent;
use Minds\Core\EventStreams\Topics\TenantBootstrapRequestsTopic;
use Minds\Core\MultiTenant\Bootstrap\Delegates\ContentGeneratedSocketDelegate;
use Minds\Core\MultiTenant\Bootstrap\Services\MultiTenantBootstrapService;
use Minds\Exceptions\CliException;
use Minds\Interfaces\CliControllerInterface;

class Bootstrap extends Controller implements CliControllerInterface
{
    public function __construct(
        private ?MultiTenantBootstrapService $service = null,
        private ?TenantBootstrapRequestsTopic $tenantBootstrapRequestsTopic = null,
    ) {
        Di::_()->get(Config::class)->set('min_log_level', 'info');
        $this->service ??= Di::_()->get(MultiTenantBootstrapService::class);
        $this->tenantBootstrapRequestsTopic ??= Di::_()->get(TenantBootstrapRequestsTopic::class);
    }

    public function help($command = null)
    {
    }

    /**
     * Bootstrap a new tenant.
     * @example
     * - php cli.php MultiTenant Bootstrap --tenantId=123 --siteUrl=https://www.minds.com/
     * @return void
     * @throws GraphQLException
     */
    public function exec(): void
    {
        $tenantId = $this->getOpt('tenantId') ? (int) $this->getOpt('tenantId') : null;
        $siteUrl = $this->getOpt('siteUrl');
        $viaEventStream = $this->getOpt('viaEventStream') ?? false;
        
        if (!$tenantId || $tenantId < 1) {
            throw new CliException('Tenant ID is a required parameter');
        }

        if (!$siteUrl) {
            throw new CliException('Site URL is a required parameter');
        }

        if (!$viaEventStream) {
            $this->service->bootstrap($siteUrl, $tenantId);
        } else {
            $event = (new TenantBootstrapRequestEvent())
                ->setTenantId($tenantId)
                ->setSiteUrl($siteUrl);
            $sent = $this->tenantBootstrapRequestsTopic->send($event);
            $this->out($sent ? 'Event sent to pulsar' : 'Event failed to send to pulsar');
        }
    }

    /**
     * Emit a content generated event to clients.
     * @example
     * - php cli.php MultiTenant Bootstrap emitContentGeneratedEvent --tenantId=123
     * @return void
     */
    public function emitContentGeneratedEvent(): void
    {
        $tenantId = $this->getOpt('tenantId') ? (int) $this->getOpt('tenantId') : null;

        if (!$tenantId) {
            throw new CliException('Tenant ID is a required parameter');
        }

        try {
            Di::_()->get(ContentGeneratedSocketDelegate::class)->onContentGenerated($tenantId);
        } catch (\Exception $e) {
            $this->out('Failed to emit content_generated event: ' . $e->getMessage());
        }
    }
}
