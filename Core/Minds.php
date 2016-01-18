<?php
/**
 * Minds main class
 */
namespace Minds\Core;

class Minds extends base
{
    public $root = __MINDS_ROOT__;
    public $legacy_lib_dir = "/lib/";
    public static $booted = false;

    /**
     * Initialise the site
     */
    public function init()
    {
        $this->initProviders();
    }

    /**
     * Initialize our providers
     * @return void
     */
    public function initProviders()
    {
        (new Config\ConfigProvider())->register();
        //(new Core\Boost\BoostProvider())->register();
        //(new Core\Data\DataProvider())->register();
        //(new Core\Events\EventsProvider())->register();
        //(new Core\Router\NavigationProvider())->register();
        //(new Core\Notification\NotificationProvider())->register();
        //(new Core\Payments\PaymentsProvider())->register();
        //(new Core\Queue\QueueProvider())->register();
        //(new Core\Router\RouterProvider())->register();
        //(new Core\Router\SecruityProvider())->register();
        //(new Core\SEO\SEOProvider())->register();
    }

    /**
     * Start the minds engine
     */
    public function start()
    {
        $this->checkInstalled();

        $this->loadConfigs();
        $this->loadLegacy();

        /*
         * If this is a multisite, then load the specific database settings
         */
        if ($this->detectMultisite()) {
            new multisite();
        }

        /**
         * Load session info
         */
        new Session();

        Security\XSRF::setCookie();

        Events\Defaults::_();
        SEO\Defaults::_();

        /**
         * Boot the system, @todo this should be oop?
         */
        \elgg_trigger_event('boot', 'system');

        /**
         * Load the plugins
         */
        new plugins();

        /**
         * Complete the boot process for both engine and plugins
         */
        elgg_trigger_event('init', 'system');

        /**
         * tell the system that we have fully booted
         */
        self::$booted = true;

        /**
         * System loaded and ready
         */
        \elgg_trigger_event('ready', 'system');
    }

    /**
     * Load settings
     */
    public function loadConfigs()
    {
        global $CONFIG;
        if (!isset($CONFIG)) {
            $CONFIG = Config::_();
        }

        // Load the system settings
        if (file_exists(__MINDS_ROOT__ . '/settings.php')) {
            include_once(__MINDS_ROOT__ . "/settings.php");
        }

        // Load mulit globals if set
        if (file_exists(__MINDS_ROOT__ . '/multi.settings.php')) {
            define('multisite', true);
            require_once(__MINDS_ROOT__ . '/multi.settings.php');
        }
    }


    /**
     * Load the legacy files for elgg
     */
    public function loadLegacy()
    {
        // load the rest of the library files from engine/lib/
        $lib_files = array(
            'elgglib.php', 'access.php',
            'configuration.php', 'cron.php',
            'entities.php', 'extender.php', 'filestore.php', 'group.php',
            'input.php', 'languages.php', 'location.php',
            'memcache.php',
            'notification.php', 'objects.php', 'output.php',
            'pagehandler.php', 'pageowner.php', 'pam.php', 'plugins.php',
            'private_settings.php', 'sessions.php',
            'sites.php', 'statistics.php',
            'user_settings.php', 'users.php', 'views.php',
            'widgets.php', 'xml.php', 'xml-rpc.php'
        );

        foreach ($lib_files as $file) {
            $file = __MINDS_ROOT__ . $this->legacy_lib_dir . $file;
            if (!include_once($file)) {
                $msg = "Could not load $file";
                throw new \InstallationException($msg);
            }
        }
    }

    public function detectMultisite()
    {
        if (file_exists(dirname(dirname(dirname(__MINDS_ROOT__))) ."/config.json")) {
            return true;
        }

        return false;
    }

    public function checkInstalled()
    {
        /**
         * If we are a multisite, we get the install status from the multisite settings
         */
        if ($this->detectMultisite()) {
            //we do this on db load.. not here
        } else {
            if (!file_exists(__MINDS_ROOT__ . '/settings.php') && !defined('__MINDS_INSTALLING__')) {
                header("Location: install.php");
                exit;
            }
        }
    }

    public static function getVersion()
    {
        return false;
    }
}
