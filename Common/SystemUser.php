<?php
namespace Minds\Common;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities\User;

class SystemUser extends User
{
    /** @var string */
    const DEFAULT_GUID = '100000000000000519';

    public function __construct(
        private ?Config $mindsConfig = null
    ) {
        $this->initializeAttributes();

        $this->mindsConfig ??= Di::_()->get('Config');

        $this->guid = $this->mindsConfig->get('system_user') ?: $this->getGUID();
        $this->name = $this->getName();
        $this->username = $this->getUsername();

        parent::__construct();
    }

    /**
     * @return string
     */
    public function getGUID(): string
    {
        return $this->guid ?: self::DEFAULT_GUID;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return 'minds';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Minds';
    }
}
