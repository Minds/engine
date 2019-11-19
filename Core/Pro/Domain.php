<?php
/**
 * Domain
 * @author edgebal
 */

namespace Minds\Core\Pro;

use Exception;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Util\StringValidator;
use Minds\Entities\User;

class Domain
{
    /** @var Config */
    protected $config;

    /** @var Repository */
    protected $repository;

    /**
     * Domain constructor.
     * @param Config $config
     * @param Repository $repository
     */
    public function __construct(
        $config = null,
        $repository = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->repository = $repository ?: new Repository();
    }

    /**
     * @param string $domain
     * @return Settings|null
     */
    public function lookup(string $domain): ?Settings
    {
        $rootDomains = $this->config->get('pro')['root_domains'] ?? [];

        if (in_array(strtolower($domain), $rootDomains, true)) {
            return null;
        }

        return $this->repository->getList([
            'domain' => $domain,
        ])->first();
    }

    /**
     * @param string $domain
     * @param string $userGuid
     * @return bool|null
     */
    public function isAvailable(string $domain, string $userGuid): ?bool
    {
        $rootDomains = $this->config->get('pro')['root_domains'] ?? [];

        if (in_array(strtolower($domain), $rootDomains, true)) {
            return false;
        }

        if (!StringValidator::isDomain($domain)) {
            return null;
        }

        $settings = $this->lookup($domain);
        return !$settings || ((string) $settings->getUserGuid() === $userGuid);
    }

    /**
     * @param Settings $settings
     * @param User|null $owner
     * @return string
     * @throws Exception
     */
    public function getIcon(Settings $settings, User $owner = null): string
    {
        if (!$owner) {
            $owner = new User();
            $owner->guid = $settings->getUserGuid();
        }

        return $owner->getIconURL('large');
    }
}
