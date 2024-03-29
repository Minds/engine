<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases;

use GuzzleHttp\Client;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchasesClientFactory;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws \Minds\Core\Di\ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(Manager::class, function (Di $di): Manager {
            return new Manager(
                giftCardsManager: $di->get(GiftCardsManager::class),
                config: $di->get('Config')
            );
        }, ['factory' => false]);
        $this->di->bind(InAppPurchasesClientFactory::class, function (Di $di): InAppPurchasesClientFactory {
            return new InAppPurchasesClientFactory();
        }, ['factory' => true]);
        $this->di->bind(Controller::class, function (Di $di): Controller {
            return new Controller();
        }, ['factory' => true]);
        $this->di->bind(Google\GoogleInAppPurchasesPubSub::class, function (Di $di): Google\GoogleInAppPurchasesPubSub {
            return new Google\GoogleInAppPurchasesPubSub();
        }, ['factory' => true]);

        $this->di->bind(Apple\AppleInAppPurchasesClient::class, function (Di $di): Apple\AppleInAppPurchasesClient {
            $mindsConfig = $di->get('Config');
            $client = new Client([
                'base_uri' => $mindsConfig->get('apple')['iap']['base_uri'],
                'timeout' => 30,
            ]);
            return new Apple\AppleInAppPurchasesClient(
                $mindsConfig,
                $client,
                $di->get(RelationalRepository::class),
                $di->get('Logger'),
            );
        }, ['factory' => true]);

        $this->di->bind(RelationalRepository::class, function (Di $di): RelationalRepository {
            return new RelationalRepository(
                mysqlHandler: $di->get('Database\MySQL\Client'),
                config: $di->get(Config::class),
                logger: $di->get('Logger'),
                entitiesBuilder: $di->get('EntitiesBuilder'),
            );
        }, ['factory' => true]);
    }
}
