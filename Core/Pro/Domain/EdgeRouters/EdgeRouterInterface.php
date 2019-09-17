<?php
/**
 * EdgeRouterInterface
 * @author edgebal
 */

namespace Minds\Core\Pro\Domain\EdgeRouters;

use Minds\Core\Pro\Settings;

interface EdgeRouterInterface
{
    public function initialize(): EdgeRouterInterface;
    public function putEndpoint(Settings $settings): bool;
}
