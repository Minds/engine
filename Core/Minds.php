<?php

namespace Minds\Core;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Interfaces\ModuleInterface;
use Minds\Helpers;

/**
 * Core Minds Engine.
 */
class Minds extends base
{
    public $root = __MINDS_ROOT__;
    public $legacy_lib_dir = '/lib/';
    public static $booted = false;

    private $modules = [
        Log\Module::class,
        Events\Module::class,
        Features\Module::class,
        SSO\Module::class,
        Email\Module::class,
        Experiments\Module::class,
        Helpdesk\Module::class,
        Onboarding\Module::class,
        Permissions\Module::class,
        Subscriptions\Module::class,
        SendWyre\Module::class,
        Suggestions\Module::class,
        Referrals\Module::class,
        Reports\Module::class,
        VideoChat\Module::class,
        Feeds\Module::class,
        Front\Module::class,
        Captcha\Module::class,
        SEO\Sitemaps\Module::class,
        Discovery\Module::class,
        Monetization\Partners\Module::class,
        Monetization\EarningsOverview\Module::class,
        Channels\Groups\Module::class,
        Media\YouTubeImporter\Module::class,
        DismissibleWidgets\Module::class,
        Wire\SupportTiers\Module::class,
        Wire\Paywall\Module::class,
        I18n\Module::class,
        Permaweb\Module::class,
    ];

    /**
     * Initializes the site.
     */
    public function init()
    {
        $this->initModules();
        $this->initProviders();
    }

    /**
     * Register our modules.
     */
    public function initModules()
    {
        $modules = [];
        foreach ($this->modules as $module) {
            $modules[] = new $module();

            // Submodules han be registered with the ->submodules[] property
            if (property_exists($module, 'submodules')) {
                foreach ($modules->submodules as $submodule) {
                    $modules[] = $submodule;
                }
            }
        }

        /*
         * Initialise the modules
         */
        /** @var ModuleInterface $module */
        foreach ($modules as $module) {
            $module->onInit();
        }
    }

    /**
     * Register our DI providers.
     */
    public function initProviders()
    {
        Di::_()->bind('Guid', function ($di) {
            return new GuidBuilder();
        }, ['useFactory' => true]);

        (new \Minds\Entities\EntitiesProvider())->register();
        (new Config\ConfigProvider())->register();
        (new Router\RouterProvider())->register();
        (new OAuth\OAuthProvider())->register();
        (new Sessions\SessionsProvider())->register();
        (new Boost\BoostProvider())->register();
        (new Data\DataProvider())->register();
        //(new Core\Notification\NotificationProvider())->register();
        (new Pages\PagesProvider())->register();
        (new Payments\PaymentsProvider())->register();
        (new Queue\QueueProvider())->register();
        (new Security\SecurityProvider())->register();
        (new Http\HttpProvider())->register();
        (new Translation\TranslationProvider())->register();
        (new Categories\CategoriesProvider())->register();
        (new ThirdPartyNetworks\ThirdPartyNetworksProvider())->register();
        (new Storage\StorageProvider())->register();
        (new Monetization\MonetizationProvider())->register();
        (new Programs\ProgramsProvider())->register();
        (new Wire\WireProvider())->register();
        (new Trending\TrendingProvider())->register();
        (new Media\MediaProvider())->register();
        (new Notification\NotificationProvider())->register();
        (new Groups\GroupsProvider())->register();
        (new Search\SearchProvider())->register();
        (new Votes\VotesProvider())->register();
        (new SMS\SMSProvider())->register();
        (new Blockchain\BlockchainProvider())->register();
        (new Issues\IssuesProvider())->register();
        (new Payments\Subscriptions\SubscriptionsProvider())->register();
        (new Faq\FaqProvider())->register();
        (new Rewards\RewardsProvider())->register();
        (new Plus\PlusProvider())->register();
        (new Pro\ProProvider())->register();
        (new Hashtags\HashtagsProvider())->register();
        (new Analytics\AnalyticsProvider())->register();
        (new Channels\ChannelsProvider())->register();
        (new Blogs\BlogsProvider())->register();
        (new Permaweb\PermawebProvider())->register();
    }

    /**
     * Start the Minds engine.
     */
    public function start()
    {
        $this->checkInstalled();
        $this->loadConfigs();
        $this->loadLegacy();
        $this->loadEvents();

        /*
        * If this is a multisite, then load the specific database settings
        */
        if ($this->detectMultisite()) {
            new multisite();
        }
    }

    /**
     * Check if Minds is installed, if not redirect to install script.
     */
    public function checkInstalled()
    {
        /*
         * If we are a multisite, we get the install status from the multisite settings
         */
        if ($this->detectMultisite()) {
            //we do this on db load.. not here
        } else {
            if (!file_exists(__MINDS_ROOT__.'/settings.php') && !defined('__MINDS_INSTALLING__')) {
                ob_end_clean();
                header('Fatal error', true, 500);
                error_log('settings.php file could not be found');
                exit;
            }
        }
    }


    /*
    * Load events
    */
    public function loadEvents()
    {
        Events\Defaults::_();
        /*
         * Boot the system, @todo this should be oop?
         */
        Dispatcher::trigger('boot', 'elgg/event/system', null, true);

        /*
         * Complete the boot process for both engine and plugins
         */
        Dispatcher::trigger('init', 'elgg/event/system', null, true);

        /*
         * tell the system that we have fully booted
         */
        self::$booted = true;

        /*
        * System loaded and ready
        */
        Dispatcher::trigger('ready', 'elgg/event/system', null, true);
    }

    /**
     * Load settings files.
     */
    public function loadConfigs()
    {
        global $CONFIG;
        if (!isset($CONFIG)) {
            $CONFIG = static::$di->get('Config');
        }

        // Load the system settings
        if (file_exists(__MINDS_ROOT__.'/settings.php')) {
            include_once __MINDS_ROOT__.'/settings.php';
        }

        // Load mulit globals if set
        if (file_exists(__MINDS_ROOT__.'/multi.settings.php')) {
            define('multisite', true);
            require_once __MINDS_ROOT__.'/multi.settings.php';
        }
        // Load environment values
        $env = Helpers\Env::getMindsEnv();
        foreach ($env as $key => $value) {
            $CONFIG->set($key, $value, ['recursive' => true]);
        }
    }

    /**
     * Load the legacy files for Elgg framework.
     *
     * @todo Deprecate this
     */
    public function loadLegacy()
    {
        // TODO: Remove when no longer needed
        $lib_files = [
            'elgglib.php',
            'access.php',
            'configuration.php',
            'entities.php',
            'extender.php',
            'filestore.php',
            //'group.php',
            'input.php',
            'languages.php',
            'memcache.php',
            //'notification.php',
            'objects.php',
            //'pagehandler.php',
            //'pageowner.php',
            'pam.php',
            //'plugins.php',
            'private_settings.php',
            'sessions.php',
            'sites.php',
            'user_settings.php',
            'users.php',
            //'xml.php',
            //'xml-rpc.php'
        ];

        foreach ($lib_files as $file) {
            $file = __MINDS_ROOT__.$this->legacy_lib_dir.$file;
            if (!include_once($file)) {
                $msg = "Could not load $file";
                throw new \InstallationException($msg);
            }
        }
    }

    /**
     * Detects if there are multisite settings present.
     *
     * @return bool
     */
    public function detectMultisite()
    {
        if (file_exists(__MINDS_ROOT__.'/multi.settings.php')) {
            return true;
        }

        return false;
    }

    /**
     * TBD. Not used.
     *
     * @return bool
     */
    public static function getVersion()
    {
        return false;
    }
}
