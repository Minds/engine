<?php

namespace Minds\Core\Media\Video\CloudflareStreams;

use GuzzleHttp\Exception\ClientException;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log;
use Minds\Core\Media\Feeds;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Security\ACL;
use Minds\Core\Storage\Quotas\Manager as StorageQuotasManager;
use Minds\Entities\Activity;
use Minds\Entities\Video;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Webhooks
{
    /** @var Client */
    protected $client;

    /** @var Config */
    protected $config;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Save */
    protected $save;

    /** @var Log\Logger */
    protected $logger;

    /** @var ACL */
    protected $acl;

    /** @var Feeds */
    protected $feeds;

    public function __construct(
        private readonly StorageQuotasManager    $storageQuotasManager,
        $client = null,
        $config = null,
        $entitiesBuilder = null,
        $save = null,
        ?ACL                                     $acl = null,
        private readonly ?MultiTenantBootService $multiTenantBootService = null,
    ) {
        $this->client = $client ?? new Client();
        $this->config = $config ?? Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->save = $save ?? new Save();
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->acl = $acl ?? Di::_()->get('Security\ACL');
        $this->multiTenantBootService ?? Di::_()->get(MultiTenantBootService::class);
    }

    /**
     * Registers a webhook and returns the secrets.
     * You must put this secret in settings.php (cloudflare->webhook_secret)
     * @return string
     */
    public function registerWebhook(): ?string
    {
        $siteUrl = $this->config->get('site_url');
        $webhookUrl = $siteUrl . 'api/v3/media/cloudflare/webhooks';

        try {
            $response = $this->client->request('PUT', 'stream/webhook', [
                'notificationUrl' => $webhookUrl
            ]);

            $decodedResponse = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            var_dump($e->getResponse()->getBody()->getContents());
            return null;
        }

        return $decodedResponse['result']['secret'];
    }

    /**
     * @param ServerRequest $request
     * @param bool $bypassAuthentication - allows bypass for testing via CLI.
     * @return JsonResponse
     */
    public function onWebhook(ServerRequest $request, bool $bypassAuthentication = false): JsonResponse
    {
        if (!$bypassAuthentication) {
            $this->verifyWebhookAuthenticity($request);
        }

        $body = $request->getParsedBody();
        $guid = $body['meta']['guid'];
        $transcodingState = $body['status']['state'];
        $tenantId = isset($body['meta']['tenant_id']) ? (int) $body['meta']['tenant_id'] : null;

        $this->logger->info('CloudflareWebhook - Video ' . $guid);

        $ia = $this->acl->setIgnore(true);

        if ($tenantId) {
            $this->multiTenantBootService->bootFromTenantId($tenantId);
        }

        /** @var Video */
        $video = $this->entitiesBuilder->single($guid);

        if (!$video || !$video instanceof Video) {
            $this->logger->error('Video not found');
            throw new UserErrorException('Invalid video guid');
        }

        // Update the width / height
        $video->width = $body['input']['width'];
        $video->height = $body['input']['height'];
        $duration = (float)$body['duration'];

        $video->setTranscodingStatus($transcodingState === 'ready' ? TranscodeStates::COMPLETED : TranscodeStates::FAILED);

        $this->logger->info("Cloudflare webhook - height: $video->height width: $video->width transcodingState: $transcodingState");
        $this->save
            ->setEntity($video)
            ->save();

        $this->patchLinkedActivity($video);

        $this->storageQuotasManager->storeVideoDuration(
            asset: $video,
            durationInSeconds: $duration,
            tenantId: $tenantId
        );

        $this->acl->setIgnore($ia); // Set the ignore state back to what it was

        if ($tenantId) {
            $this->multiTenantBootService->resetRootConfigs();
        }

        return new JsonResponse([]);
    }

    /**
     * Verifies whether the webhook is authentic
     * @param ServerRequest $request
     * @return void
     * @throws UserErrorException
     */
    private function verifyWebhookAuthenticity(ServerRequest $request): void
    {
        $secret = $this->config->get('cloudflare')['webhook_secret'];

        $signature = $request->getHeader('Webhook-Signature');
        $signatureParts = explode(',', $signature[0]);

        $signatureTs = explode('=', $signatureParts[0])[1];
        $signatureSig1 = explode('=', $signatureParts[1])[1];

        if ($signatureTs < time() - 300) {
            $this->logger->error('CloudflareWebhook - timestamp INVALID');
            throw new UserErrorException('Invalid signature - time is invalid');
        }

        $expectedSignature = hash_hmac('sha256', $signatureTs . '.' . $request->getBody(), $secret);

        if ($expectedSignature !== $signatureSig1) {
            $this->logger->error('CloudflareWebhook - signature INVALID');
            throw new UserErrorException('Invalid signature - expected ' . $expectedSignature);
        }

        $this->logger->info('CloudflareWebhook - signature ok');
    }

    /**
     * Patch linked activity with height and width from video.
     * @param Video $video - video to patch from.
     * @return void
     */
    private function patchLinkedActivity(Video $video): void
    {
        $activity = $this->entitiesBuilder->single($video->getContainerGuid());

        if (!$activity || !($activity instanceof Activity)) {
            return;
        }

        $activity->setAttachments([$video]);

        $this->save
            ->setEntity($activity)
            ->save();
    }
}
