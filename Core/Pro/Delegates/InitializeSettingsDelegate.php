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

    /** @var SetupRoutingDelegate */
    protected $setupRoutingDelegate;

    /**
     * InitializeSettingsDelegate constructor.
     * @param Repository $repository
     * @param SetupRoutingDelegate $setupRoutingDelegate
     */
    public function __construct(
        $repository = null,
        $setupRoutingDelegate = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->setupRoutingDelegate = $setupRoutingDelegate ?: new SetupRoutingDelegate();
    }

    /**
     * @param User $user
     * @throws Exception
     */
    public function onEnable(User $user): void
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

        if (!$settings->getTitle()) {
            $settings->setTitle($user->name ?: $user->username);
        }

        $this->setupRoutingDelegate
            ->onUpdate($settings);

        $this->repository
            ->add($settings);
    }
}
