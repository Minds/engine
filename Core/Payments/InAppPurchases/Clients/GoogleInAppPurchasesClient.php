<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Clients;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Entities\User;

class GoogleInAppPurchasesClient implements InAppPurchaseClientInterface
{
    private const PACKAGE_NAME = "com.minds.mobile";
    private const API_BASE_URI = "https://androidpublisher.googleapis.com/androidpublisher/v3/applications/" . self::PACKAGE_NAME . "/purchases/subscriptions/";

    public function __construct(
        private ?MindsConfig $mindsConfig = null,
        private ?HttpClient $httpClient = null
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->httpClient ??= new HttpClient([
            'base_uri' => self::API_BASE_URI
        ]);
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @param User $user
     * @return bool
     */
    public function acknowledgePurchase(InAppPurchase $inAppPurchase): bool
    {
        $response = $this->httpClient->postAsync(
            uri: "$inAppPurchase->subscriptionId/tokens/$inAppPurchase->purchaseToken:acknowledge",
            options: [
                RequestOptions::HEADERS => [
                    "Content-Type" => 'application/json; charset=utf-8'
                ],
                RequestOptions::QUERY => [
                    'key' => $this->mindsConfig->get('google')['iap']['api_key']
                ],
                RequestOptions::JSON => [
                    'developerPayload' => json_encode([
                        '' => ''
                    ])
                ]
            ]
        );

        /**
         * @var Response $opResult
         */
        $opResult = $response->wait(true);

        return $opResult->getStatusCode() === 200;
    }
}
