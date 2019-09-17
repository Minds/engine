<?php
/**
 * SetupRoutingDelegate
 * @author edgebal
 */

namespace Minds\Core\Pro\Delegates;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Settings;

class SetupRoutingDelegate
{
    /** @var Config */
    protected $config;

    /**
     * SetupRoutingDelegate constructor.
     * @param Config $config
     */
    public function __construct(
        $config = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
    }

    public function onUpdate(Settings $settings)
    {
        $userGuid = $settings->getUserGuid();

        if (!$settings->getDomain()) {
            $settings->setDomain(sprintf("pro-%s.%s", $userGuid, $this->config->get('pro')['subdomain_prefix'] ?? 'minds.com'));
        }

        // TODO: Ping load balancer
    }
}
