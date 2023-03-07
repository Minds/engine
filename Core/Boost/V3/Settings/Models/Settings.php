<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Settings\Models;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Boost\V3\Settings\BoostPartnerSuitability;
use Minds\Traits\MagicAttributes;

/**
 * Settings model for Boosts V3
 * @method int getBoostPartnerSuitability()
 * @method Settings setBoostPartnerSuitability(int $key)
 */
class Settings implements \JsonSerializable
{
    use MagicAttributes;

    public function __construct(
        private ?int $boostPartnerSuitability = null,
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get('Config');
        // show controversial boosts unless user has specified otherwise
        $this->boostPartnerSuitability ??= BoostPartnerSuitability::CONTROVERSIAL;
    }

    /**
     * Export object as array.
     * @return array - array containing object data.
     */
    public function export(): array
    {
        return [
            'boost_partner_suitability' => $this->boostPartnerSuitability,
        ];
    }

    /**
     * Called on JSON serialization.
     * @return array - array that will be JSON serialized.
     */
    public function jsonSerialize(): array
    {
        return $this->export();
    }
}
