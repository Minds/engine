<?php
namespace Minds\Core\Captcha\FriendlyCaptcha;

use Minds\Interfaces\ModuleInterface;

/**
 * FriendlyCaptcha module
 */
class Module implements ModuleInterface
{
    /**
     * OnInit
     */
    public function onInit()
    {
        (new Provider())->register();
        (new Routes())->register();
    }
}
