<?php

namespace Minds\Core\Queue\Runners;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Invites\Services\InviteProcessorService;
use Minds\Core\Email\Services\EmailAutoSubscribeService;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Services\FeaturedEntityAutoSubscribeService;
use Minds\Core\Queue\Client;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Core\Queue\Interfaces\QueueRunner;
use Minds\Core\Queue\Message;
use Minds\Entities\User;

class Registered implements QueueRunner
{
    private readonly EmailAutoSubscribeService $emailAutoSubscribeService;
    private readonly FeaturedEntityAutoSubscribeService $featuredEntityAutoSubscribeService;
    private readonly InviteProcessorService $inviteProcessorService;
    private readonly QueueClient $client;
    private readonly EntitiesBuilder $entitiesBuilder;
    private readonly Config $config;

    public function __construct(
        ?EmailAutoSubscribeService          $emailAutoSubscribeService = null,
        ?FeaturedEntityAutoSubscribeService $featuredEntityAutoSubscribeService = null,
        ?InviteProcessorService             $inviteProcessorService = null,
        ?EntitiesBuilder                    $entitiesBuilder = null,
        ?QueueClient                        $client = null,
        ?Config                             $config = null
    ) {
        $this->emailAutoSubscribeService = $emailAutoSubscribeService ?? Di::_()->get(EmailAutoSubscribeService::class);
        $this->featuredEntityAutoSubscribeService = $featuredEntityAutoSubscribeService ?? Di::_()->get(FeaturedEntityAutoSubscribeService::class);
        $this->inviteProcessorService = $inviteProcessorService ?? Di::_()->get(InviteProcessorService::class);
        $this->client = $client ?? Client::build();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->config = $config ?? Di::_()->get(Config::class);
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $this->client->setQueue("Registered")
            ->receive([$this, 'processPostRegistrationEvent']);

        $this->run();
    }

    /**
     * @param Message $message
     * @return bool
     * @throws Exception
     */
    public function processPostRegistrationEvent(Message $message): bool
    {
        $userGuid = $message->getData()['user_guid'];
        $tenantId = $this->config->get('tenant_id') ?? null;

        /** @var User $subscriber */
        $subscriber = $this->entitiesBuilder->single($userGuid);
        if (!$subscriber) {
            return false;
        }

        try {
            if (!$tenantId) { // no tenant id means we are on the main site
                $subscriber->subscribe('100000000000000519');
                echo "[registered]: User registered $userGuid\n";
            } else {
                $this->featuredEntityAutoSubscribeService
                    ->autoSubscribe($subscriber, $tenantId);
                echo "[registered]: User auto subscribed to featured entities for tenant $tenantId\n";
            }
        } catch (Exception $e) {
            echo "[registered]: Failed to auto subscribe user $userGuid\n";
        }

        try {
            // Process invite token if any
            if ($inviteToken = $message->getData()['invite_token']) {
                $this->inviteProcessorService->processInvite($subscriber, $inviteToken);
                echo "[registered]: Processed invite with token $inviteToken\n";
            }
        } catch (Exception $e) {
            echo "[registered]: Failed to process invite with token $inviteToken\n";
        }

        try {
            // Subscribe to default email notifications
            $this->emailAutoSubscribeService->subscribeToDefaultEmails((int)$userGuid);
            echo "[registered]: subscribed {$userGuid} to default email notifications \n";
        } catch (Exception $e) {
            echo "[registered]: Failed to subscribe {$userGuid} to default email notifications \n";
        }

        return true;
    }
}
