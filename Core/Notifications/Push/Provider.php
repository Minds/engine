<?php
/**
 * Minds Push Notifications Provider.
 */

namespace Minds\Core\Notifications\Push;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Notifications\Push\Config\PushNotificationsConfigRepository;
use Minds\Core\Notifications\Push\Config\PushNotificationsConfigService;
use Minds\Core\Notifications\Push\Services\ApnsService;
use Minds\Core\Notifications\Push\Services\FcmService;
use Minds\Core\Notifications\Push\Services\WebPushService;

/**
 * Notifications Provider
 * @package Minds\Core\Notifications
 */
class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Notifications\Push\Manager', function ($di) {
            return new Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\DeviceSubscriptions\Controller', function ($di) {
            return new DeviceSubscriptions\Controller();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\Settings\Manager', function ($di) {
            return new Settings\Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\Settings\Controller', function ($di) {
            return new Settings\Controller();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\System\Controller', function ($di) {
            return new System\Controller();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\System\Manager', function ($di) {
            return new System\Manager();
        }, ['useFactory' => false]);
        $this->di->bind('Notifications\Push\TopPost\Manager', function ($di) {
            return new TopPost\Manager();
        }, ['useFactory' => false]);

        $this->di->bind(ApnsService::class, function (Di $di): ApnsService {
            return new ApnsService(
                client: new \GuzzleHttp\Client(),
                config: $di->get(Config::class),
                pushNotificationsConfigService: $di->get(PushNotificationsConfigService::class),
            );
        });
        $this->di->bind(FcmService::class, function (Di $di): FcmService {
            return new FcmService(
                client: new \GuzzleHttp\Client(),
                config: $di->get(Config::class),
                pushNotificationsConfigService: $di->get(PushNotificationsConfigService::class),
            );
        });
        $this->di->bind(WebPushService::class, function (Di $di): WebPushService {
            return new WebPushService(
                client: new \GuzzleHttp\Client(),
                config: $di->get(Config::class),
                pushNotificationsConfigService: $di->get(PushNotificationsConfigService::class),
            );
        });

        /**
         * Push Configs
         */
        $this->di->bind(PushNotificationsConfigService::class, function (Di $di): PushNotificationsConfigService {
            return new PushNotificationsConfigService($di->get(PushNotificationsConfigRepository::class));
        });
        $this->di->bind(PushNotificationsConfigRepository::class, function (Di $di): PushNotificationsConfigRepository {
            return new PushNotificationsConfigRepository(
                $di->get(MySQL\Client::class),
                $di->get(Config::class),
                $di->get('Logger')
            );
        });

        $this->di->bind(
            DeviceSubscriptions\Manager::class,
            fn (Di $di): DeviceSubscriptions\Manager => new DeviceSubscriptions\Manager()
        );
    }
}
