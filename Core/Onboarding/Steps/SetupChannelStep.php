<?php
/**
 * Setup channel.
 *
 * @author Mark Harding
 */

namespace Minds\Core\Onboarding\Steps;

use Minds\Entities\User;
use Minds\Core\Config;
use Minds\Core\Di\Di;

class SetupChannelStep implements OnboardingStepInterface
{
    /** @var Config $config */
    private $config;

    /**
     * Manager constructor.
     * @param Config $config
     */
    public function __construct($config = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isCompleted(User $user)
    {
        $displayNameStep = new DisplayNameStep();
        $avatarStep = new AvatarStep($this->config);
        $bioStep = new BriefdescriptionStep();
        return $displayNameStep->isCompleted($user)
            && $avatarStep->isCompleted($user)
            && $bioStep->isCompleted($user);
    }
}
