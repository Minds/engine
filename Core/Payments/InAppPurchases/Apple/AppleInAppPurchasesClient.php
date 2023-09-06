<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Apple;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Uri;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Payments\InAppPurchases\Apple\Types\JWSTransactionInfo;
use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchaseClientInterface;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use NotImplementedException;

class AppleInAppPurchasesClient implements InAppPurchaseClientInterface
{
    private const APP_BUNDLE_ID = "com.minds.mobile";
    private const JWT_ALGORITHM = "ES256";
    private const JWT_TYPE = "JWT";
    private const JWT_AUDIENCE = "appstoreconnect-v1";

    private readonly string $key;

    public function __construct(
        private readonly MindsConfig $mindsConfig,
        private readonly HttpClient $client,
        private readonly Logger $logger
    ) {
        $this->key = file_get_contents($this->mindsConfig->get('apple')['iap']['private_key_path']);
    }

    /**
     * TODO
     */
    public function acknowledgeSubscription(InAppPurchase $inAppPurchase): bool
    {
        return false;
    }

    /**
     * @param string $transactionId
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransaction(string $transactionId): JWSTransactionInfo
    {
        $response = $this->client->get(
            uri: new Uri("/inApps/v1/transactions/$transactionId"),
            options: [
                'headers' => [
                    "Authorization" => "Bearer {$this->generateJWTToken()}"
                ]
            ]
        );

        $content = json_decode($response->getBody()->getContents());

        $jwtHandler = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->key));
        $jwtHandler->setParser(new Parser(new JoseEncoder()));
        $token = $jwtHandler->parser()->parse($content->signedTransactionInfo);
        return JWSTransactionInfo::fromToken($token);
    }

    private function generateJWTToken(): string
    {
        $issuedAtTimestamp = time();
        $jwtBuilder = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->key));
        return $jwtBuilder->builder()
            // Set token headers
            ->withHeader('alg', self::JWT_ALGORITHM)
            ->withHeader('kid', $this->mindsConfig->get('apple')['iap']['key_id'])
            ->withHeader('typ', self::JWT_TYPE)
            // Set token claims
            ->withClaim('iss', $this->mindsConfig->get('apple')['iap']['issuer_id'])
            ->withClaim('iat', $issuedAtTimestamp)
            ->withClaim('exp', strtotime('+60 minutes', $issuedAtTimestamp))
            ->withClaim('aud', self::JWT_AUDIENCE)
            ->withClaim('bid', self::APP_BUNDLE_ID)
            // Build and sign token
            ->getToken($jwtBuilder->signer(), $jwtBuilder->signingKey())->toString();
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @return mixed
     * @throws NotImplementedException
     */
    public function getSubscription(InAppPurchase $inAppPurchase): mixed
    {
        throw new NotImplementedException();
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @return mixed
     * @throws NotImplementedException
     */
    public function getInAppPurchaseProductPurchase(InAppPurchase $inAppPurchase): mixed
    {
        throw new NotImplementedException();
    }
}
