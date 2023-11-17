<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Services;

use Minds\Core\Channels\Ban;
use Minds\Core\Comments\Comment;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\Comments\Manager as CommentManager;
use Minds\Core\Reports\Enums\ReportActionEnum;
use Minds\Core\Reports\V2\Types\Report;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
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
    ) {
    }

    /**
     * Handle a report - applying relevant action.
     * @param Report $report - report to handle.
     * @param ReportActionEnum $action - action to perform.
     * @return void
     */
    public function handleReport(Report $report, ReportActionEnum $action): void
    {
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
                $this->deleteEntity($entity);
                break;
        }
    }

    /**
     * Ban a user.
     * @param User $user - user to ban.
     * @param Report $report - report to ban user for.
     * @return void
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
     * @return void
     */
    private function deleteEntity(mixed $entity)
    {
        if ($entity instanceof Comment) {
            $this->commentManager->delete($entity);
            return;
        }
        $this->deleteAction->setEntity($entity)->delete();
    }
}
