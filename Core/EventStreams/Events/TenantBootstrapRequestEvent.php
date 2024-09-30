<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Events;

use Minds\Core\EventStreams\AcknowledgmentEventTrait;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\TimebasedEventTrait;
use Minds\Traits\MagicAttributes;

/**
 * @method self setTenantId(int $tenantId)
 * @method self setSiteUrl(string $siteUrl)
 * @method int getTenantId()
 * @method string getSiteUrl()
 */
class TenantBootstrapRequestEvent implements EventInterface
{
    use MagicAttributes;
    use AcknowledgmentEventTrait;
    use TimebasedEventTrait;

    private int $tenantId;
    private string $siteUrl;
}
