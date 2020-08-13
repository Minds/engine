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
use Minds\Core\EntitiesBuilder;

class Domain
{
    /** @var Config */
    protected $config;

    /** @var Repository */
    protected $repository;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Manager */
    protected $proManager;

    /**
     * Domain constructor.
     * @param Config $config
     * @param Repository $repository
     */
    public function __construct(
        $config = null,
        $repository = null,
        $entitiesBuilder = null,
        $proManager = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->repository = $repository ?: new Repository();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->proManager = $proManager ?? Di::_()->get('Pro\Manager');
    }

    /**
     * @param string $domain
     * @return Settings|null
     */
    public function lookup(string $domain): ?Settings
    {
        if (!$domain) {
            return null;
        }

        $rootDomains = $this->config->get('pro')['root_domains'] ?? [];

        if (in_array(strtolower($domain), $rootDomains, true)) {
            return null;
        }

        $settings = $this->repository->getList([
            'domain' => $domain,
        ])->first();

        if (!$settings) {
            return null;
        }

        $user = $this->entitiesBuilder->single($settings->getUserGuid());

        if (!$user) {
            return null;
        }
    
        return $this->proManager
            ->setUser($user)
            ->hydrate($settings);
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
     * @param string $domain
     * @return bool
     */
    public function isRoot(string $domain): bool
    {
        $rootDomains = $this->config->get('pro')['root_domains'] ?? [];
        return in_array(strtolower($domain), $rootDomains, true);
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
