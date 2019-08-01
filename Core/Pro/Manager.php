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

    /** @var Delegates\HydrateSettingsDelegate */
    protected $hydrateSettingsDelegate;

    /** @var User */
    protected $user;

    /**
     * Manager constructor.
     * @param Repository $repository
     * @param Save $saveAction
     * @param Delegates\InitializeSettingsDelegate $initializeSettingsDelegate
     * @param Delegates\HydrateSettingsDelegate $hydrateSettingsDelegate
     */
    public function __construct(
        $repository = null,
        $saveAction = null,
        $initializeSettingsDelegate = null,
        $hydrateSettingsDelegate = null
    )
    {
        $this->repository = $repository ?: new Repository();
        $this->saveAction = $saveAction ?: new Save();
        $this->initializeSettingsDelegate = $initializeSettingsDelegate ?: new Delegates\InitializeSettingsDelegate();
        $this->hydrateSettingsDelegate = $hydrateSettingsDelegate ?: new Delegates\HydrateSettingsDelegate();
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

        $settings = $this->repository->getList([
            'user_guid' => $this->user->guid
        ])->first();

        if (!$settings) {
            return null;
        }

        return $this->hydrateSettingsDelegate
            ->onGet($this->user, $settings);
    }

    /**
     * @param array $values
     * @return bool
     * @throws Exception
     */
    public function set(array $values = [])
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        $settings = $this->get() ?: new Settings();

        $settings
            ->setUserGuid($this->user->guid);

        if (isset($values['domain'])) {
            // TODO: Validate!

            $settings
                ->setDomain($values['domain']);
        }

        if (isset($values['title'])) {
            // TODO: Validate!

            $settings
                ->setTitle($values['title']);
        }

        if (isset($values['headline'])) {
            // TODO: Validate!

            $settings
                ->setHeadline($values['headline']);
        }

        if (isset($values['text_color'])) {
            // TODO: Validate!

            $settings
                ->setTextColor($values['text_color']);
        }

        if (isset($values['primary_color'])) {
            // TODO: Validate!

            $settings
                ->setPrimaryColor($values['primary_color']);
        }

        if (isset($values['plain_background_color'])) {
            // TODO: Validate!

            $settings
                ->setPlainBackgroundColor($values['plain_background_color']);
        }

        if (isset($values['logo_guid'])) {
            // TODO: Validate!

            $settings
                ->setLogoGuid($values['logo_guid']);
        }

        if (isset($values['footer_text'])) {
            // TODO: Validate!

            $settings
                ->setFooterText($values['footer_text']);
        }

        if (isset($values['footer_links']) && is_array($values['footer_links'])) {
            $footerLinks = array_map(function ($item) {
                $href = $item['href'];
                $title = ($item['title'] ?? null) ?: $item['href'];

                return compact('title', 'href');
            }, array_filter($values['footer_links'], function ($item) {
                return $item && $item['href'] && filter_var($item['href'], FILTER_VALIDATE_URL);
            }));

            $settings
                ->setFooterLinks(array_values($footerLinks));
        }

        return $this->repository->update($settings);
    }
}
