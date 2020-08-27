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

    /** @var Delegates\HydrateSettingsDelegate */
    protected $hydrateSettingsDelegate;

    /** @var Delegates\SetupRoutingDelegate */
    protected $setupRoutingDelegate;

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
     * @param Delegates\HydrateSettingsDelegate    $hydrateSettingsDelegate
     * @param Delegates\SetupRoutingDelegate       $setupRoutingDelegate
     * @param Delegates\SubscriptionDelegate       $subscriptionDelegate
     */
    public function __construct(
        $repository = null,
        $saveAction = null,
        $entitiesBuilder = null,
        $initializeSettingsDelegate = null,
        $hydrateSettingsDelegate = null,
        $setupRoutingDelegate = null,
        $subscriptionDelegate = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->saveAction = $saveAction ?: new Save();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->initializeSettingsDelegate = $initializeSettingsDelegate ?: new Delegates\InitializeSettingsDelegate();
        $this->hydrateSettingsDelegate = $hydrateSettingsDelegate ?: new Delegates\HydrateSettingsDelegate();
        $this->setupRoutingDelegate = $setupRoutingDelegate ?: new Delegates\SetupRoutingDelegate();
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
    public function disable(): bool
    {
        if (!$this->user) {
            throw new Exception('Invalid user');
        }

        $this->subscriptionDelegate
            ->onDisable($this->user);

        $this->user
            ->setProExpires(0);

        $saved = $this->saveAction
            ->setEntity($this->user)
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
            $settings->setTitle($this->user->name ?: $this->user->username);
        }

        if (!$settings) {
            return null;
        }

        return $this->hydrate($settings);
    }

    /**
     * @param Settings $settings
     *
     * @return Settings
     */
    public function hydrate(Settings $settings): Settings
    {
        return $this->hydrateSettingsDelegate
            ->onGet($this->user, $settings);
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

        if (isset($values['domain'])) {
            $domain = trim($values['domain']);

            if (!StringValidator::isDomain($domain)) {
                throw new \Exception('Invalid domain');
            }

            $settings
                ->setDomain($domain);
        }

        if (isset($values['title'])) {
            $title = trim($values['title']);

            if (strlen($title) > 60) {
                throw new \Exception('Title must be 60 characters or less');
            }

            $settings
                ->setTitle($title);
        }

        if (isset($values['headline'])) {
            $headline = trim($values['headline']);

            if (strlen($headline) > 80) {
                throw new \Exception('Headline must be 80 characters or less');
            }

            $settings
                ->setHeadline($headline);
        }

        if (isset($values['text_color'])) {
            if (!StringValidator::isHexColor($values['text_color'])) {
                throw new \Exception('Text color must be a valid hex color');
            }

            $settings
                ->setTextColor($values['text_color']);
        }

        if (isset($values['primary_color'])) {
            if (!StringValidator::isHexColor($values['primary_color'])) {
                throw new \Exception('Primary color must be a valid hex color');
            }

            $settings
                ->setPrimaryColor($values['primary_color']);
        }

        if (isset($values['plain_background_color'])) {
            if (!StringValidator::isHexColor($values['plain_background_color'])) {
                throw new \Exception('Plain background color must be a valid hex color');
            }
            $settings
                ->setPlainBackgroundColor($values['plain_background_color']);
        }

        if (isset($values['tile_ratio'])) {
            if (!in_array($values['tile_ratio'], Settings::TILE_RATIOS, true)) {
                throw new \Exception('Invalid tile ratio');
            }

            $settings
                ->setTileRatio($values['tile_ratio']);
        }

        if (isset($values['footer_text'])) {
            $footer_text = trim($values['footer_text']);

            if (strlen($footer_text) > 80) {
                throw new \Exception('Footer text must be 80 characters or less');
            }

            $settings
                ->setFooterText($footer_text);
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

        if (isset($values['tag_list']) && is_array($values['tag_list'])) {
            $tagList = array_map(function ($item) {
                $tag = trim($item['tag'], "#\t\n\r");
                $label = ($item['label'] ?? null) ?: "#{$item['tag']}";

                return compact('label', 'tag');
            }, array_filter($values['tag_list'], function ($item) {
                return $item && $item['tag'];
            }));

            $settings
                ->setTagList(array_values($tagList));
        }

        if (isset($values['scheme'])) {
            if (!in_array($values['scheme'], Settings::COLOR_SCHEMES, true)) {
                throw new \Exception('Invalid tile ratio');
            }
            $settings
                ->setScheme($values['scheme']);
        }

        if (isset($values['custom_head']) && $this->actor->isAdmin()) {
            $settings
                ->setCustomHead($values['custom_head']);
        }

        if (isset($values['has_custom_logo'])) {
            $settings
                ->setHasCustomLogo((bool) $values['has_custom_logo']);
        }

        if (isset($values['has_custom_background'])) {
            $settings
                ->setHasCustomBackground((bool) $values['has_custom_background']);
        }

        if (isset($values['published'])) {
            $this->user->setProPublished($values['published']);
            $this->saveAction
                ->setEntity($this->user)
                ->save();
        }

        if (isset($values['splash'])) {
            $settings->setSplash($values['splash']);
        }

        if (isset($values['payout_method'])) {
            $settings->setPayoutMethod($values['payout_method']);
        }

        $settings->setTimeUpdated(time());

        // Only update routing if we are active
        if ($this->isActive()) {
            $this->setupRoutingDelegate
                ->onUpdate($settings);
        }

        return $this->repository->update($settings);
    }
}
