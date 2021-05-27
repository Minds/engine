<?php
namespace Minds\Common;

use Minds\Entities\User;

class SystemUser extends User
{
    /** @var string */
    const GUID = '100000000000000519';

    /**
     * @return string
     */
    public function getGUID()
    {
        return self::GUID;
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
