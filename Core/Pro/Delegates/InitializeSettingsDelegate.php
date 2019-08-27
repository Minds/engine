<?php
/**
 * InitializeSettingsDelegate
 * @author edgebal
 */

namespace Minds\Core\Pro\Delegates;

use Exception;
use Minds\Core\Pro\Repository;
use Minds\Core\Pro\Settings;
use Minds\Entities\User;

class InitializeSettingsDelegate
{
    /** @var Repository */
    protected $repository;

    /**
     * InitializeSettingsDelegate constructor.
     * @param Repository $repository
     */
    public function __construct(
        $repository = null
    ) {
        $this->repository = $repository ?: new Repository();
    }

    /**
     * @param User $user
     * @throws Exception
     */
    public function onEnable(User $user)
    {
        /** @var Settings|null $settings */
        $settings = $this->repository
            ->getList(['user_guid' => $user->guid])
            ->first();

        if (!$settings) {
            $settings = new Settings();
            $settings
                ->setUserGuid($user->guid);
        }

        if (!$settings->getDomain()) {
            $settings->setDomain("pro-{$user->guid}.minds.com");
        }

        if (!$settings->getTitle()) {
            $settings->setTitle($user->name ?: $user->username);
        }

        $this->repository
            ->add($settings);
    }
}
