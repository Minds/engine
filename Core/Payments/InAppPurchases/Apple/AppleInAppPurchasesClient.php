<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Apple;

use DateTimeImmutable;
use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\InAppPurchases\Apple\Enums\AppleSubscriptionStatusEnum;
use Minds\Core\Payments\InAppPurchases\Apple\Types\AppleConsumablePurchase;
use Minds\Core\Payments\InAppPurchases\Apple\Types\AppleSubscription;
use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchaseClientInterface;
use Minds\Core\Payments\InAppPurchases\Enums\InAppPurchaseTypeEnum;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Core\Payments\InAppPurchases\RelationalRepository;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Exceptions\ServerErrorException;
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
        private readonly RelationalRepository $relationalRepository,
        private readonly Logger $logger
    ) {
        $this->key = file_get_contents($this->mindsConfig->get('apple')['iap']['private_key_path']);
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @return bool
     * @throws GuzzleException
     * @throws ServerErrorException
     */
    public function acknowledgeSubscription(InAppPurchase $inAppPurchase): bool
    {
        $appleIAPSubscription = $this->getSubscription($inAppPurchase);

        if ($appleIAPSubscription->subscriptionStatus !== AppleSubscriptionStatusEnum::ACTIVE) {
            return false;
        }

        $this->relationalRepository->storeInAppPurchaseTransaction($appleIAPSubscription->originalTransactionId, $inAppPurchase);

        return true;
    }

    /**
     * @param string $transactionId
     * @return AppleConsumablePurchase
     * @throws GuzzleException
     */
    public function getTransaction(string $transactionId): AppleConsumablePurchase
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
        /**
         * @var UnencryptedToken $token
         */
        $token = $jwtHandler->parser()->parse($content->signedTransactionInfo);
        return AppleConsumablePurchase::fromToken($token);
    }

    /**
     * @param string $payload
     * @return UnencryptedToken
     */
    public function decodeSignedPayload(string $payload): UnencryptedToken
    {
        $jwtHandler = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->key));
        $jwtHandler->setParser(new Parser(new JoseEncoder()));
        return $jwtHandler->parser()->parse($payload);
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

            ->issuedBy($this->mindsConfig->get('apple')['iap']['issuer_id'])
            ->issuedAt((new DateTimeImmutable())->setTimestamp($issuedAtTimestamp))
            ->expiresAt((new DateTimeImmutable())->setTimestamp(strtotime('+60 minutes', $issuedAtTimestamp)))
            ->permittedFor(self::JWT_AUDIENCE)

            ->withClaim('bid', self::APP_BUNDLE_ID)
            // Build and sign token
            ->getToken($jwtBuilder->signer(), $jwtBuilder->signingKey())->toString();
    }

    /**
     * @param InAppPurchase $inAppPurchase
     * @return mixed
     * @throws GuzzleException
     * @throws Exception
     */
    public function getSubscription(InAppPurchase $inAppPurchase): AppleSubscription
    {
        $receivedTransactionDetails = $this->decodeSignedPayload($inAppPurchase->iosTransactionPayload);

        if ($this->relationalRepository->getInAppPurchaseTransaction($receivedTransactionDetails->claims()->get('originalTransactionId'))) {
            throw new ForbiddenException("This purchase has already been processed. Please contact support.");
        }

        $response = $this->client->get(
            uri: new Uri("/inApps/v1/subscriptions/{$receivedTransactionDetails->claims()->get('originalTransactionId')}"),
            options: [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->generateJWTToken(),
                ]
            ]
        );

        $inAppPurchase->purchaseType = InAppPurchaseTypeEnum::SUBSCRIPTION;
        $inAppPurchase->transactionTimestamp = $receivedTransactionDetails->claims()->get('originalPurchaseDate');

        if ($response->getStatusCode() !== 200) {
            throw new Exception($response->getBody()->getContents(), $response->getStatusCode());
        }

        $content = json_decode($response->getBody()->getContents());

        return new AppleSubscription(
            originalTransactionId: $content->data[0]->lastTransactions[0]->originalTransactionId,
            subscriptionStatus: AppleSubscriptionStatusEnum::tryFrom((int) $content->data[0]->lastTransactions[0]->status),
            signedRenewalInfo: $content->data[0]->lastTransactions[0]->signedRenewalInfo,
            signedTransactionInfo: $content->data[0]->lastTransactions[0]->signedTransactionInfo,
        );
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


    /**
     * @param string $transactionId
     * @return InAppPurchase
     * @throws ServerErrorException
     */
    public function getOriginalSubscriptionDetails(string $transactionId): InAppPurchase
    {
        return $this->relationalRepository->getInAppPurchaseTransaction($transactionId);
    }
}
