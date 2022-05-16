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
use Minds\Core\Security\ACL;
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
        $client = null,
        $config = null,
        $entitiesBuilder = null,
        $save = null,
        ?Feeds $feeds = null,
        ?ACL $acl = null
    ) {
        $this->client = $client ?? new Client();
        $this->config = $config ?? Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->save = $save ?? new Save();
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->acl = $acl ?? Di::_()->get('Security\ACL');
        $this->feeds = $feeds ?? Di::_()->get('Media\Feeds');
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
     * @return JsonResponse
     */
    public function onWebhook(ServerRequest $request): JsonResponse
    {
        $this->verifyWebhookAuthenticity($request);
        
        $body = $request->getParsedBody();
        $guid = $body['meta']['guid'];
        $transcodingState = $body['status']['state'];

        $this->logger->info('CloudflareWebhook - Video ' . $guid);

        $ia = $this->acl->setIgnore(true);

        /** @var Video */
        $video = $this->entitiesBuilder->single($guid);

        if (!$video || !$video instanceof Video) {
            $this->logger->error('Video not found');
            throw new UserErrorException('Invalid video guid');
        }

        // Update the width / height
        $video->width = $body['input']['width'];
        $video->height = $body['input']['height'];

        $video->setTranscodingStatus($transcodingState === 'ready' ? TranscodeStates::COMPLETED : TranscodeStates::FAILED);

        $this->logger->info("Cloudflare webhook - height: $video->height width: $video->width transcodingState: $transcodingState");
        $this->save
            ->setEntity($video)
            ->save();

        // propagate properties from video to activity.
        $this->feeds->setEntity($video)->updateActivities();

        $this->acl->setIgnore($ia); // Set the ignore state back to what it was
    
        return new JsonResponse([ ]);
    }

    /**
     * Verifies whether the webhook is authentic
     * @param ServerRequest $request
     * @throws UserErrorException
     * @return void
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
}
