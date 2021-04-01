<?php
namespace Minds\Core\Matrix;

use Minds\Core;
use Minds\Core\Di\Di;

class MatrixConfig
{
    /** @var Core\Config */
    protected $config;

    public function __construct(Core\Config $config = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * @return tring
     */
    public function getAdminAccessToken(): string
    {
        return $this->get('admin_access_token');
    }

    /**
     * Used for talking to _synapse/_matrix servers
     * eg. minds-com.ems.host
     * @return string
     */
    public function getHomeserverApiDomain(): string
    {
        return $this->get('homeserver_api_domain');
    }

    /**
     * Used to build the matrix ids. The friendly name of your homeserver
     * eg. minds.com
     * @return tring
     */
    public function getHomeserverDomain(): string
    {
        return $this->get('homeserver_domain');
    }

    /**
     * Returns a matrix config key
     * @param string $key
     * @return mixed
     */
    protected function get(string $key)
    {
        return $this->config->get('matrix')[$key] ?? null;
    }
}
