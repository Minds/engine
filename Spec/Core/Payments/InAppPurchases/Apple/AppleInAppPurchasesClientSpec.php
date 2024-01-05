<?php
namespace Spec\Minds\Core\Payments\InAppPurchases\Apple;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\InAppPurchases\Apple\AppleInAppPurchasesClient;
use Minds\Core\Payments\InAppPurchases\Apple\Enums\AppleSubscriptionStatusEnum;
use Minds\Core\Payments\InAppPurchases\Apple\Types\AppleConsumablePurchase;
use Minds\Core\Payments\InAppPurchases\Apple\Types\AppleSubscription;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;
use Minds\Core\Payments\InAppPurchases\RelationalRepository;
use NotImplementedException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class AppleInAppPurchasesClientSpec extends ObjectBehavior
{
    private Collaborator $clientMock;
    private Collaborator $loggerMock;
    private Collaborator $relationRepositoryMock;

    private string $privateKeyFilepath;

    // This is a purposely made key for this unit test. It IS NOT used anywhere else.
    private const PEM_PRIVATE_KEY = "-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIPWdgLY5K0Vk6M62+M9yypwbV1HzLrFf7wcMcuAwulZloAoGCCqGSM49
AwEHoUQDQgAE5yYSUUztQ47dDtu+2aD8xwMNJUhRe7EeC3wtxE+IJZYh8zbQgs/O
ytJLQ68Bt5f1331SMCslX8y68/vI82UhEQ==
-----END EC PRIVATE KEY-----
";

    public function let(
        HttpClient $clientMock,
        Logger $loggerMock,
        RelationalRepository $relationalRepositoryMock
    ): void {
        $config = Di::_()->get('Config');

        $this->privateKeyFilepath = sys_get_temp_dir() . DIRECTORY_SEPARATOR .  md5(rand());

        file_put_contents($this->privateKeyFilepath, self::PEM_PRIVATE_KEY);

        $config->set('apple', [
            'iap' => [
                'private_key_path' => $this->privateKeyFilepath,
                'key_id' => 'key-id',
                'issuer_id' => 'issuer-id'
            ]
        ]);

        $this->clientMock = $clientMock;
        $this->relationRepositoryMock = $relationalRepositoryMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith(
            $config,
            $clientMock,
            $relationalRepositoryMock,
            $loggerMock
        );
    }

    public function letGo(): void
    {
        unlink($this->privateKeyFilepath);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(AppleInAppPurchasesClient::class);
    }

    public function it_should_retrieve_a_transaction(): void
    {
        $jwtBuilder = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::PEM_PRIVATE_KEY));
        $jwtBuilder->setParser(new Parser(new JoseEncoder()));
        $responseMock = new Response(
            status: 200,
            body: json_encode([
                'signedTransactionInfo' => $jwtBuilder->builder()
                    ->withClaim('originalPurchaseDate', time())
                    ->withClaim('bundleId', 'bundle-id')
                    ->withClaim('environment', 'environment')
                    ->withClaim('originalTransactionId', 'original-transaction-id')
                    ->withClaim('productId', 'product-id')
                    ->getToken($jwtBuilder->signer(), $jwtBuilder->signingKey())->toString()
            ])
        );

        $transactionID = "transaction-id";
        $this->clientMock->get(
            Argument::that(
                fn (Uri $uri): bool => $uri->getPath() === "/inApps/v1/transactions/$transactionID"
            ),
            Argument::that(
                fn (array $options): bool => isset($options['headers']['Authorization'])
            )
        )
            ->shouldBeCalledOnce()
            ->willReturn($responseMock);

        $this->getTransaction($transactionID)->shouldBeAnInstanceOf(AppleConsumablePurchase::class);
    }

    public function it_should_acknowledge_subscription(): void
    {
        $jwtBuilder = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::PEM_PRIVATE_KEY));
        $jwtBuilder->setParser(new Parser(new JoseEncoder()));

        $transactionID = 'original-transaction-id';

        $inAppPurchaseMock = new InAppPurchase(
            source: AppleInAppPurchasesClient::class,
            purchaseToken: "",
            subscriptionId: "plus.monthly.01",
            iosTransactionPayload: $jwtBuilder->builder()
                ->withClaim('originalPurchaseDate', time())
                ->withClaim('originalTransactionId', 'original-transaction-id')
                ->getToken($jwtBuilder->signer(), $jwtBuilder->signingKey())->toString(),
        );

        $responseMock = new Response(
            status: 200,
            body: json_encode([
                'data' => [
                    [
                        'lastTransactions' => [
                            [
                                'originalTransactionId' => 'original-transaction-id',
                                'status' => AppleSubscriptionStatusEnum::ACTIVE->value,
                                'signedRenewalInfo' => 'signed-renewal-info',
                                'signedTransactionInfo' => 'signed-transaction-info'
                            ]
                        ]
                    ]
                ]
            ])
        );

        $this->clientMock->get(
            Argument::that(
                fn (Uri $uri): bool => $uri->getPath() === "/inApps/v1/subscriptions/$transactionID"
            ),
            Argument::that(
                fn (array $options): bool => isset($options['headers']['Authorization'])
            )
        )
            ->shouldBeCalledOnce()
            ->willReturn($responseMock);

        $this->relationRepositoryMock->getInAppPurchaseTransaction($transactionID)
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->relationRepositoryMock->storeInAppPurchaseTransaction($transactionID, $inAppPurchaseMock)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->acknowledgeSubscription($inAppPurchaseMock)->shouldReturn(true);
    }

    public function it_should_return_a_subscription_object_with_current_status(): void
    {
        $jwtBuilder = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::PEM_PRIVATE_KEY));
        $jwtBuilder->setParser(new Parser(new JoseEncoder()));

        $transactionID = 'original-transaction-id';

        $inAppPurchaseMock = new InAppPurchase(
            source: AppleInAppPurchasesClient::class,
            purchaseToken: "",
            subscriptionId: "plus.monthly.01",
            iosTransactionPayload: $jwtBuilder->builder()
                ->withClaim('originalPurchaseDate', time())
                ->withClaim('originalTransactionId', 'original-transaction-id')
                ->getToken($jwtBuilder->signer(), $jwtBuilder->signingKey())->toString(),
        );

        $responseMock = new Response(
            status: 200,
            body: json_encode([
                'data' => [
                    [
                        'lastTransactions' => [
                            [
                                'originalTransactionId' => 'original-transaction-id',
                                'status' => AppleSubscriptionStatusEnum::ACTIVE->value,
                                'signedRenewalInfo' => 'signed-renewal-info',
                                'signedTransactionInfo' => 'signed-transaction-info'
                            ]
                        ]
                    ]
                ]
            ])
        );

        $this->clientMock->get(
            Argument::that(
                fn (Uri $uri): bool => $uri->getPath() === "/inApps/v1/subscriptions/$transactionID"
            ),
            Argument::that(
                fn (array $options): bool => isset($options['headers']['Authorization'])
            )
        )
            ->shouldBeCalledOnce()
            ->willReturn($responseMock);

        $this->getSubscription($inAppPurchaseMock)->shouldReturnAnInstanceOf(AppleSubscription::class);
    }

    public function it_should_throw_not_implemented_exception_when_getting_product_purchase(
        InAppPurchase $inAppPurchaseMock
    ): void {
        $this->shouldThrow(NotImplementedException::class)->during('getInAppPurchaseProductPurchase', [$inAppPurchaseMock]);
    }
}
