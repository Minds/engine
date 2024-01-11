<?php
namespace Minds\Core\Notifications\Push\Services;

use DateTimeImmutable;
use GuzzleHttp\Exception\GuzzleException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Minds\Core\Notifications\Push\Config\PushNotificationConfig;
use Minds\Core\Notifications\Push\PushNotificationInterface;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Core\Notifications\Push\UndeliverableException;
use Psr\Http\Message\ResponseInterface;

class ApnsService extends AbstractService implements PushServiceInterface
{
    protected string $cachedJwt;
    protected int $cachedJwtTs = 0;

    /**
     * @param PushNotificationInterface $pushNotification
     * @return bool
     * @throws GuzzleException
     */
    public function send(PushNotificationInterface $pushNotification): bool
    {
        $alert = array_filter([
            'title' => $pushNotification->getTitle(),
            'body' => $pushNotification->getBody(),
        ]);
        
        if (!($pushNotification instanceof CustomPushNotification)) {
            $alert = [
                'body' => $pushNotification->getTitle() . ($pushNotification->getBody() ? ": {$pushNotification->getBody()}" : "")
            ];
        }

        $payload = [
            'aps' => [
                "mutable-content" => 1,
                'alert' => $alert,
                'badge' => $pushNotification->getUnreadCount(),
            ],
            'uri' => $pushNotification->getUri(),
            'user_guid' => $pushNotification->getDeviceSubscription()->getUserGuid(),
            'largeIcon' => $pushNotification->getIcon(),
            'metadata' => json_encode($pushNotification->getMetadata())
        ];

        $headers = [
            'apns-collapse-id' => $pushNotification->getMergeKey(),
        ];

        try {
            $this->request($pushNotification->getDeviceSubscription()->getToken(), $headers, $payload);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @param string $deviceToken
     * @param array $headers
     * @param array $body
     * @return ResponseInterface
     * @throws GuzzleException
     */
    protected function request($deviceToken, array $headers, array $body): ResponseInterface
    {
        $uri = "https://api.push.apple.com/3/device/";

        if ($this->config->get('apple')['sandbox']) {
            $uri = "https://api.sandbox.push.apple.com/3/device/";
        }

        $pushConfig = $this->pushNotificationsConfigService->get($this->getTenantId());

        if (!$pushConfig) {
            throw new UndeliverableException("Push has not been configured for tenant " . $this->getTenantId());
        }

        $headers['apns-topic'] = $pushConfig->apnsTopic;

        $headers['Authorization']=  'Bearer ' . $this->buildJwt($pushConfig);
    
        $json = $this->client->request('POST', $uri . $deviceToken, [
                    'version' => 2,
                    'headers' => $headers,
                    'json' => $body
                ]);
       
        return $json;
    }

    /**
     * Builds the JWT token
     */
    protected function buildJwt(PushNotificationConfig $pushConfig): string
    {
        if ($this->cachedJwtTs > time() - 3600) {
            return $this->cachedJwt;
        }

        $jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($pushConfig->apnsKey));
        $builder = $jwtConfig->builder();
        $this->cachedJwt = $builder
            ->issuedBy($pushConfig->apnsTeamId)
            ->issuedAt(new DateTimeImmutable('now'))
            ->withHeader('kid', $pushConfig->apnsKeyId)
            ->getToken($jwtConfig->signer(), $jwtConfig->signingKey())
            ->toString();

        $this->cachedJwtTs = time();

        return $this->cachedJwt;
    }

    /**
     * @return string
     */
    protected function getFirebaseApiKey(): string
    {
        return $this->config->get('google')['push'];
    }
}
