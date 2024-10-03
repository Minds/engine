<?php
/**
 * Manager.
 *
 * @author edgebal
 */

namespace Minds\Core\Pro;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Util\StringValidator;
use Minds\Entities\User;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var Save */
    protected $saveAction;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Delegates\InitializeSettingsDelegate */
    protected $initializeSettingsDelegate;

    /** @var Delegates\SubscriptionDelegate */
    protected $subscriptionDelegate;

    /** @var User */
    protected $user;

    /** @var User */
    protected $actor;

    /**
     * Manager constructor.
     *
     * @param Repository                           $repository
     * @param Save                                 $saveAction
     * @param EntitiesBuilder                      $entitiesBuilder
     * @param Delegates\InitializeSettingsDelegate $initializeSettingsDelegate
     * @param Delegates\SubscriptionDelegate       $subscriptionDelegate
     */
    public function __construct(
        $repository = null,
        $saveAction = null,
        $entitiesBuilder = null,
        $initializeSettingsDelegate = null,
        $subscriptionDelegate = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->saveAction = $saveAction ?: new Save();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->initializeSettingsDelegate = $initializeSettingsDelegate ?: new Delegates\InitializeSettingsDelegate();
        $this->subscriptionDelegate = $subscriptionDelegate ?: new Delegates\SubscriptionDelegate();
    }

    /**
     * @param User $user
     *
     * @return Manager
     */
    public function setUser(User $user): Manager
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param User $actor
     *
     * @return Manager
     */
    public function setActor(User $actor): Manager
    {
        $this->actor = $actor;

        return $this;
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function isActive(): bool
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        return $this->user->isPro();
    }

    /**
     * @param $until
     *
     * @return bool
     *
     * @throws Exception
     */
    public function enable($until): bool
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        $this->user
            ->setProExpires($until);

        $saved = $this->saveAction
            ->setEntity($this->user)
            ->withMutatedAttributes([
                'pro_expires',
            ])
            ->save();

        $this->initializeSettingsDelegate
            ->onEnable($this->user);

        return (bool) $saved;
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function disable($remove = false): bool
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        if ($remove) {
            $this->user->setProExpires(time());
        }

        $this->subscriptionDelegate
            ->onDisable($this->user);

        $saved = $this->saveAction
            ->setEntity($this->user)
            ->withMutatedAttributes([
                'pro_expires',
            ])
            ->save();

        return (bool) $saved;
    }

    /**
     * @return Settings|null
     *
     * @throws Exception
     */
    public function get(): ?Settings
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        $settings = $this->repository->getList([
            'user_guid' => $this->user->guid,
        ])->first();

        // If requested by an inactive user, this is preview mode
        if (!$settings && !$this->isActive()) {
            $settings = new Settings();
            $settings->setUserGuid($this->user->guid);
        }

        return $settings ?? null;
    }

    /**
     * @param array $values
     *
     * @return bool
     *
     * @throws Exception
     */
    public function set(array $values = []): bool
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        $settings = $this->get() ?: new Settings();

        $settings
            ->setUserGuid($this->user->guid);

        if (isset($values['payout_method'])) {
            if ($this->user->isPro()) {
                if (
                    $this->user->getProMethod() === 'tokens' &&
                    $values['payout_method'] !== 'tokens'
                ) {
                    throw new \Exception('Pro token customers can only receive token payouts');
                }
            } elseif (
                $this->user->isPlus() &&
                $this->user->getPlusMethod() === 'tokens' &&
                $values['payout_method'] !== 'tokens'
            ) {
                throw new \Exception('Plus token customers can only receive token payouts');
            }
            $settings->setPayoutMethod($values['payout_method']);
        }

        $settings->setTimeUpdated(time());

        return $this->repository->update($settings);
    }
}
