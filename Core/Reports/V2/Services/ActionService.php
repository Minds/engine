<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Services;

use Minds\Core\Channels\Ban;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Exceptions\ChatMessageNotFoundException;
use Minds\Core\Chat\Services\MessageService as ChatMessageService;
use Minds\Core\Comments\Comment;
use Minds\Core\Comments\Manager as CommentManager;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Reports\Enums\ReportActionEnum;
use Minds\Core\Reports\V2\Types\Report;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\StopEventException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * Service for the handling of performing actions upon a report.
 */
class ActionService
{
    public function __construct(
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly EntitiesResolver $entitiesResolver,
        private readonly CommentManager $commentManager,
        private readonly Ban $channelsBanManager,
        private readonly Delete $deleteAction,
        private readonly ChatMessageService $chatMessageService
    ) {
    }

    /**
     * Handle a report - applying relevant action.
     * @param Report $report - report to handle.
     * @param ReportActionEnum $action - action to perform.
     * @return void
     */
    public function handleReport(
        Report $report,
        ReportActionEnum $action,
        User $moderator
    ): void {
        if (!$entity = $this->entitiesResolver->single($report->entityUrn)) {
            throw new NotFoundException('Entity not found with urn: ' . $report->entityUrn);
        }

        switch ($action) {
            case ReportActionEnum::BAN:
                if (!$entity instanceof User) {
                    if ($entity->getOwnerGuid()) {
                        $entity = $this->entitiesBuilder->single($entity->getOwnerGuid());
                    }

                    if (!$entity || !$entity instanceof User) {
                        throw new NotFoundException('Entity owner not found');
                    }
                }
                $this->banUser(user: $entity, report: $report);
                break;
            case ReportActionEnum::DELETE:
                if ($entity instanceof User) {
                    throw new GraphQLException('Users cannot be deleted');
                }
                $this->deleteEntity($entity, $moderator);
                break;
        }
    }

    /**
     * Ban a user.
     * @param User $user - user to ban.
     * @param Report $report - report to ban user for.
     * @return void
     * @throws \Exception
     */
    private function banUser(User $user, Report $report): void
    {
        $this->channelsBanManager
            ->setUser($user)
            ->ban(implode('.', [ $report->reason->value, $report->getSubReason()?->value ]));
    }

    /**
     * Delete an entity.
     * @param mixed $entity - entity to delete.
     * @param User $moderator
     * @return void
     * @throws GraphQLException
     * @throws StopEventException
     * @throws ChatMessageNotFoundException
     * @throws ServerErrorException
     */
    private function deleteEntity(
        mixed $entity,
        User $moderator
    ): void {
        if ($entity instanceof ChatMessage) {
            $this->chatMessageService->deleteMessage(
                roomGuid: $entity->roomGuid,
                messageGuid: $entity->guid,
                loggedInUser: $moderator
            );
            return;
        }
        if ($entity instanceof Comment) {
            $this->commentManager->delete($entity);
            return;
        }
        $this->deleteAction->setEntity($entity)->delete();
    }
}
