<?php
/**
 * Config
 *
 * @author edgebal
 */

namespace Minds\Core\Features\Services;

use InvalidArgumentException;
use Minds\Core\Config as MindsConfig;
use Minds\Core\Di\Di;

/**
 * Static config (settings.php) feature flags service
 * @package Minds\Core\Features\Services
 */
class Config extends BaseService
{
    /** @var MindsConfig */
    protected $config;

    /**
     * Config constructor.
     * @param Config $config
     */
    public function __construct(
        $config = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @inheritDoc
     */
    public function fetch(array $keys): array
    {
        // Return whitelisted 'features' array with its values resolved

        return array_intersect_key(
            array_map(
                [$this, '_resolveValue'],
                $this->config->get('features') ?: []
            ),
            array_flip($keys)
        );
    }

    /**
     * Resolve strings to groups. Boolean are returned as is. Other types throw an exception.
     * @param mixed $value
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function _resolveValue($value): bool
    {
        if (is_string($value)) {
            return in_array(strtolower($value), $this->getUserGroups(), true);
        } elseif (is_bool($value)) {
            return $value;
        }

        throw new InvalidArgumentException();
    }
}
