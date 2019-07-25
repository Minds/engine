<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Pro;

use Exception;
use Minds\Core\Entities\Actions\Save;
use Minds\Entities\User;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var Save */
    protected $saveAction;

    /** @var Delegates\InitializeSettingsDelegate */
    protected $initializeSettingsDelegate;

    /** @var User */
    protected $user;

    /**
     * Manager constructor.
     * @param Repository $repository
     * @param Save $saveAction
     * @param Delegates\InitializeSettingsDelegate $initializeSettingsDelegate
     */
    public function __construct(
        $repository = null,
        $saveAction = null,
        $initializeSettingsDelegate = null
    )
    {
        $this->repository = $repository ?: new Repository();
        $this->saveAction = $saveAction ?: new Save();
        $this->initializeSettingsDelegate = $initializeSettingsDelegate ?: new Delegates\InitializeSettingsDelegate();
    }

    /**
     * @param User $user
     * @return Manager
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isActive()
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        return $this->user->isPro();
    }

    /**
     * @param $until
     * @return bool
     * @throws Exception
     */
    public function enable($until)
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        $this->user
            ->setProExpires($until);

        $saved = $this->saveAction
            ->setEntity($this->user)
            ->save();

        $this->initializeSettingsDelegate
            ->onEnable($this->user);

        return (bool) $saved;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function disable()
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        // TODO: Disable subscription instead, let Pro expire itself at the end of the sub

        $this->user
            ->setProExpires(0);

        $saved = $this->saveAction
            ->setEntity($this->user)
            ->save();

        return (bool) $saved;
    }

    /**
     * @return Settings|null
     * @throws Exception
     */
    public function get()
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        return $this->repository->getList([
            'user_guid' => $this->user->guid
        ])->first();
    }

    /**
     * @param array $settings
     * @return bool
     * @throws Exception
     */
    public function set(array $settings = [])
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        $settings = $this->get() ?: new Settings();

        $settings
            ->setUserGuid($this->user->guid);

        if (isset($settings['domain'])) {
            // TODO: Validate!

            $settings
                ->setDomain($settings['domain']);
        }

        if (isset($settings['title'])) {
            // TODO: Validate!

            $settings
                ->setTitle($settings['title']);
        }

        if (isset($settings['headline'])) {
            // TODO: Validate!

            $settings
                ->setHeadline($settings['headline']);
        }

        if (isset($settings['text_color'])) {
            // TODO: Validate!

            $settings
                ->setTextColor($settings['text_color']);
        }

        if (isset($settings['primary_color'])) {
            // TODO: Validate!

            $settings
                ->setPrimaryColor($settings['primary_color']);
        }

        if (isset($settings['plain_background_color'])) {
            // TODO: Validate!

            $settings
                ->setPlainBackgroundColor($settings['plain_background_color']);
        }

        return $this->repository->update($settings);
    }
}
