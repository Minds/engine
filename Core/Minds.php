<?php

namespace Minds\Core;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Helpers;
use Minds\Helpers\Env;
use Minds\Interfaces\ModuleInterface;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Core Minds Engine.
 * @OA\Info(title="Minds engine API", version="1.0.0")
 */
class Minds extends base
{
    public $root = __MINDS_ROOT__;
    public $legacy_lib_dir = '/lib/';
    public static $booted = false;

    private $modules = [
        Log\Module::class,
        Events\Module::class,
        GraphQL\Module::class,
        MultiTenant\Module::class,
        EventStreams\Module::class,
        Security\Module::class,
        OAuth\Module::class,
        Features\Module::class,
        Email\Module::class,
        Experiments\Module::class,
        Onboarding\Module::class,
        Permissions\Module::class,
        Subscriptions\Module::class,
        SendWyre\Module::class,
        Suggestions\Module::class,
        Referrals\Module::class,
        Reports\Module::class,
        VideoChat\Module::class,
        Feeds\Module::class,
        Captcha\Module::class,
        SEO\Sitemaps\Module::class,
        Discovery\Module::class,
        Search\Module::class,
        Monetization\Partners\Module::class,
        Monetization\EarningsOverview\Module::class,
        Channels\Groups\Module::class,
        Media\YouTubeImporter\Module::class,
        DismissibleWidgets\Module::class,
        Wire\SupportTiers\Module::class,
        Wire\Paywall\Module::class,
        I18n\Module::class,
        Permaweb\Module::class,
        Media\Proxy\Module::class,
        Blockchain\Module::class,
        Boost\Module::class,
        OEmbed\Module::class,
        Rewards\Module::class,
        Rewards\Restrictions\Blockchain\Module::class,
        Media\Video\CloudflareStreams\Module::class,
        Matrix\Module::class,
        Sessions\Module::class,
        Register\Module::class,
        Notifications\Module::class,
        Votes\Module::class,
        Helpdesk\Module::class,
        SocialCompass\Module::class,
        AccountQuality\Module::class,
        Recommendations\Module::class,
        Captcha\FriendlyCaptcha\Module::class,
        DID\Module::class,
        Nostr\Module::class,
        Entities\Ops\Module::class,
        FeedNotices\Module::class,
        Metrics\Module::class,
        Supermind\Module::class,
        Twitter\Module::class,
        Entities\Module::class,
        Payments\Stripe\Module::class,
        Authentication\Module::class,
        Payments\Module::class,
        Verification\Module::class,
        Settings\Module::class,
        Boost\V3\Module::class,
        Monetization\Module::class,
        Analytics\Module::class,
        Groups\V2\Module::class,
        Webfinger\Module::class,
        ActivityPub\Module::class,
        Admin\Module::class,
        Expo\Module::class
    ];

    /**
     * Initializes the site.
     */
    public function init()
    {
        $this->initProviders();
        $this->initModules();
    }

    /**
     * Register our modules.
     */
    public function initModules()
    {
        $modules = [];
        foreach ($this->modules as $module) {
            $modules[] = $hydratedModule = new $module();

            // Submodules han be registered with the ->submodules[] property
            if (property_exists($module, 'submodules')) {
                foreach ($hydratedModule->submodules as $submodule) {
                    $modules[] = new $submodule();
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

        (new Config\ConfigProvider())->register();
        (new \Minds\Entities\EntitiesProvider())->register();
        (new Router\RouterProvider())->register();
        (new Data\DataProvider())->register();
        //(new Core\Notification\NotificationProvider())->register();
        (new Payments\PaymentsProvider())->register();
        (new Queue\QueueProvider())->register();
        (new Http\HttpProvider())->register();
        (new Translation\TranslationProvider())->register();
        (new Categories\CategoriesProvider())->register();
        (new Storage\StorageProvider())->register();
        (new Monetization\MonetizationProvider())->register();
        (new Wire\WireProvider())->register();
        (new Trending\TrendingProvider())->register();
        (new Media\MediaProvider())->register();
        (new Notification\NotificationProvider())->register();
        (new Groups\GroupsProvider())->register();
        (new Comments\Provider())->register();
        (new SMS\SMSProvider())->register();
        (new Blockchain\BlockchainProvider())->register();
        (new Issues\IssuesProvider())->register();
        (new Payments\Subscriptions\SubscriptionsProvider())->register();
        (new Faq\FaqProvider())->register();
        (new Plus\PlusProvider())->register();
        (new Pro\ProProvider())->register();
        (new Hashtags\HashtagsProvider())->register();
        (new Channels\ChannelsProvider())->register();
        (new Blogs\BlogsProvider())->register();
        (new Permaweb\PermawebProvider())->register();
        (new Supermind\Provider())->register();
    }

    /**
     * Start the Minds engine.
     */
    public function start()
    {
        $this->checkInstalled();
        $this->loadLegacy();
        $this->loadEvents();
    }

    /**
     * Check if Minds is installed, if not redirect to install script.
     */
    public function checkInstalled()
    {
        $multiTenantConfig = Di::_()->get(Config\Config::class)->get('multi_tenant') ?? [];
        if (php_sapi_name() !== 'cli' && ($multiTenantConfig['enabled'] ?? false)) {
            /** @var MultiTenant\Services\MultiTenantBootService */
            $service = Di::_()->get(MultiTenant\Services\MultiTenantBootService::class);
            
            try {
                $service
                    ->bootFromRequest(ServerRequestFactory::fromGlobals());
            } catch (NoTenantFoundException $e) {
                if (ob_get_contents()) {
                    ob_end_clean();
                }
                header('Not found', true, 404);
                exit;
            }
        }

        if (!file_exists(__MINDS_ROOT__ . '/settings.php') && !defined('__MINDS_INSTALLING__') && php_sapi_name() !== 'cli') {
            ob_end_clean();
            header('Fatal error', true, 500);
            error_log('settings.php file could not be found');
            exit;
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
     * Load the legacy files for Elgg framework.
     *
     * @todo Deprecate this
     */
    public function loadLegacy()
    {
        // TODO: Remove when no longer needed
        $lib_files = [
            'elgglib.php',
            'entities.php',
            'input.php',
            'sessions.php',
            'users.php',
        ];

        foreach ($lib_files as $file) {
            $file = __MINDS_ROOT__ . $this->legacy_lib_dir . $file;
            if (!include_once($file)) {
                $msg = "Could not load $file";
                throw new \InstallationException($msg);
            }
        }
    }


    /**
     * TBD. Not used.
     *
     * @return bool
     */
    public static function getVersion()
    {
        return '0.0';
    }
}
