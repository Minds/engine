<?php

namespace Minds\Core\Queue\Runners;

use Minds\Core\Di\Di;
use Minds\Core\Email\EmailSubscription;
use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Services\InviteManagementService;
use Minds\Core\Email\Invites\Services\InviteReaderService;
use Minds\Core\Email\Invites\Services\InvitesService;
use Minds\Core\Email\Repository;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Manager as GroupsMembershipManager;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Notifications\PostSubscriptions\Services\PostSubscriptionsService;
use Minds\Core\Queue;
use Minds\Core\Queue\Interfaces\QueueRunner;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\User;

class Registered implements QueueRunner
{
    public function run()
    {
        $config = Di::_()->get('Config');
        $subscriptions = $config->get('default_email_subscriptions');
        /** @var Repository $repository */
        $repository = Di::_()->get('Email\Repository');

        /** @var EntitiesBuilder */
        $entitiesBuilder = Di::_()->get(EntitiesBuilder::class);

        /** @var PostSubscriptionsService **/
        $postSubscriptionsService = Di::_()->get(PostSubscriptionsService::class);

        $client = Queue\Client::Build();
        $client->setQueue("Registered")
            ->receive(function ($data) use ($subscriptions, $repository, $entitiesBuilder, $config, $postSubscriptionsService) {
                $data = $data->getData();
                $user_guid = $data['user_guid'];
                $tenant_id = $config->get('tenant_id') ?? null;

                //subscribe to minds channel
                /** @var User $subscriber */
                $subscriber = $entitiesBuilder->single($user_guid);

                if (!$tenant_id) { // no tenant id means we are on the main site
                    $subscriber->subscribe('100000000000000519');

                    echo "[registered]: User registered $user_guid\n";
                } else {
                    /**
                     * @var FeaturedEntityService $featuredEntityService
                     */
                    $featuredEntityService = Di::_()->get(FeaturedEntityService::class);

                    $featuredUsers = $featuredEntityService->getAllFeaturedEntities($tenant_id);
                    foreach ($featuredUsers as $featuredUser) {
                        if (!$featuredUser->autoSubscribe) {
                            continue;
                        }

                        $subscriber->subscribe($featuredUser->entityGuid);

                        // Post notifications
                        if ($featuredUser->autoPostSubscription) {
                            $featuredUserEntity = $entitiesBuilder->single($featuredUser->entityGuid);

                            $postSubscriptionsService->withEntity($featuredUserEntity)
                                ->withUser($subscriber)
                                ->subscribe(PostSubscriptionFrequencyEnum::ALWAYS);
                        }
                    }
                }

                // Process invite token if any
                if ($data['invite_token']) {
                    // Fetch invite
                    /**
                     * @var InviteReaderService $invitesReaderService
                     */
                    $invitesReaderService = Di::_()->get(InviteReaderService::class);

                    /**
                     * @var InviteManagementService $invitesManagementService
                     */
                    $invitesManagementService = Di::_()->get(InviteManagementService::class);
                    $invite = $invitesReaderService->getInviteByToken($data['invite_token']);

                    // Set user roles if any
                    if ($invite->getRoles()) {
                        /**
                         * @var RolesService $rolesService
                         */
                        $rolesService = Di::_()->get(RolesService::class);
                        foreach ($invite->getRoles() as $role) {
                            $rolesService->assignUserToRole(
                                $subscriber,
                                $role
                            );
                        }
                    }

                    // Subscribe to groups if any
                    if ($invite->getGroups()) {
                        /**
                         * @var GroupsMembershipManager $groupsMembershipManager
                         */
                        $groupsMembershipManager = Di::_()->get(GroupsMembershipManager::class);
                        foreach ($invite->getGroups() as $groupGuid) {
                            $group = $entitiesBuilder->single($groupGuid);
                            $groupsMembershipManager->joinGroup(
                                group: $group,
                                user: $subscriber,
                                membershipLevel: GroupMembershipLevelEnum::MEMBER
                            );
                        }
                    }

                    $invitesManagementService->updateInviteStatus($invite->inviteId, InviteEmailStatusEnum::ACCEPTED);
                }

                foreach ($subscriptions as $subscription) {
                    $sub = array_merge($subscription, ['userGuid' => $user_guid]);
                    $repository->add(new EmailSubscription($sub));
                }

                echo "[registered]: subscribed {$user_guid} to default email notifications \n";
            });
        $this->run();
    }
}
