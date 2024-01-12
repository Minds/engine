<?php
namespace Minds\Core\Notifications\Push\Services;

use GuzzleHttp;
use Minds\Core\Config\Config;
use Minds\Core\Notifications\Push\Config\PushNotificationsConfigService;

abstract class AbstractService
{
    public function __construct(
        protected GuzzleHttp\Client $client,
        protected Config $config,
        protected PushNotificationsConfigService $pushNotificationsConfigService,
    ) {
    }

    /**
     * If not a tenant, we use -1 as our tenant id
     */
    protected function getTenantId(): int
    {
        return $this->config->get('tenant_id') ?: -1;
    }
}
