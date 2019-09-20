<?php
/**
 * SetupRoutingDelegate
 * @author edgebal
 */

namespace Minds\Core\Pro\Delegates;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Domain\EdgeRouters\EdgeRouterInterface;
use Minds\Core\Pro\Domain\EdgeRouters\TraefikDynamoDb;
use Minds\Core\Pro\Settings;

class SetupRoutingDelegate
{
    /** @var Config */
    protected $config;

    /** @var EdgeRouterInterface */
    protected $edgeRouter;

    /**
     * SetupRoutingDelegate constructor.
     * @param Config $config
     * @param EdgeRouterInterface $edgeRouter
     */
    public function __construct(
        $config = null,
        $edgeRouter = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->edgeRouter = $edgeRouter ?: new TraefikDynamoDb();
    }

    /**
     * @param Settings $settings
     */
    public function onUpdate(Settings $settings): void
    {
        $userGuid = $settings->getUserGuid();

        if (!$settings->getDomain()) {
            $settings->setDomain(sprintf("pro-%s.%s", $userGuid, $this->config->get('pro')['subdomain_suffix'] ?? 'minds.com'));
        }

        $success = $this->edgeRouter
            ->initialize()
            ->putEndpoint($settings);

        if (!$success) {
            error_log("[MindsPro] Cannot setup endpoint.");
            // TODO: Implement user-facing warning
        }
    }
}
