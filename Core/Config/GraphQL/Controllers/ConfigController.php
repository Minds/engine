<?php
namespace Minds\Core\Config\GraphQL\Controllers;

use Minds\Core\Config\Config;
use Minds\Core\Router\Exceptions\ForbiddenException;
use TheCodingMachine\GraphQLite\Annotations\Query;

class ConfigController
{
    public function __construct(private Config $config)
    {
        
    }

    #[Query]
    /**
     * Returns key value configs
     */
    public function getConfig(string $key): ?string
    {
        $allowedKeys = [
            'site_name',
            'site_url',
            'theme_override.color_scheme',
            'theme_override.primary_color',
        ];

        if (!in_array($key, $allowedKeys, true)) {
            throw new ForbiddenException("Key is not allowed");
        }

        $keyPath = explode('.', $key);

        $result = $this->config->get(array_shift($keyPath));

        if (!is_array($result)) {
            return $result;
        }

        return $this->getRecursive($keyPath, $result);
    }

    /**
     * Returns a config value recursively
     */
    private function getRecursive(array $keyPath, array $config)
    {
        $key = array_shift($keyPath);
        $value = $config[$key] ?? null;
        if (is_array($value)) {
            return $this->getRecursive($keyPath, $value);
        } else {
            return $value;
        }
    }

}
