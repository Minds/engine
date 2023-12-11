<?php
/**
 * Action delegate for Verdicts
 */
namespace Minds\Core\Reports\Verdict\Delegates;

use Minds\Common\Urn;
use Minds\Core\Boost\V3\Enums\BoostRejectionReason;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Comments\Comment;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Log\Logger;
use Minds\Core\Monetization\Demonetization\DemonetizationContext;
use Minds\Core\Monetization\Demonetization\Strategies\DemonetizePlusUserStrategy;
use Minds\Core\Monetization\Demonetization\Strategies\DemonetizePostStrategy;
use Minds\Core\Plus;
use Minds\Core\Reports\Report;
use Minds\Core\Reports\Strikes\Strike;
use Minds\Core\Reports\Verdict\Verdict;
use Minds\Core\Security\ACL;
use Minds\Core\Security\Password;
use Minds\Core\Sessions;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\FederatedEntityInterface;

class ActionDelegate
{
    /** @var EntitiesBuilder $entitiesBuilder */
    private $entitiesBuilder;

    /** @var Actions $actions */
    private $actions;

    /** @var SaveAction $saveAction */
    private $saveAction;

    /** @var Urn $urn */
    private $urn;

    /** @var StrikesManager $strikesManager */
    private $strikesManager;

    /** @var EmailDelegate $emailDelegate */
    private $emailDelegate;

    /** @var Core\Channels\Ban $channelsBanManager */
    private $channelsBanManager;

    /** @var Plus\Manager */
    protected $plusManager;

    /** @var Sessions\CommonSessions\Manager */
    protected $commonSessionsManager;

    /** @var Password */
    protected $password;

    public function __construct(
        $entitiesBuilder = null,
        $actions = null,
        $urn = null,
        $strikesManager = null,
        $saveAction = null,
        $emailDelegate = null,
        $channelsBanManager = null,
        $plusManager = null,
        $commonSessionsManager = null,
        $password = null,
        private ?DemonetizationContext $demonetizationContext = null,
        private ?DemonetizePostStrategy $demonetizePostStrategy = null,
        private ?DemonetizePlusUserStrategy $demonetizePlusUserStrategy = null,
        private ?BoostManager $boostManager = null,
        private ?Logger $logger = null,
        private ?ActivityPubReportDelegate $activityPubReportDelegate = null
    ) {
        $this->entitiesBuilder = $entitiesBuilder  ?: Di::_()->get('EntitiesBuilder');
        $this->actions = $actions ?: Di::_()->get('Reports\Actions');
        $this->urn = $urn ?: new Urn;
        $this->strikesManager = $strikesManager ?: Di::_()->get('Moderation\Strikes\Manager');
        $this->saveAction = $saveAction ?: new SaveAction;
        $this->emailDelegate = $emailDelegate ?: new EmailDelegate;
        $this->channelsBanManager = $channelsBanManager ?: Di::_()->get('Channels\Ban');
        $this->plusManager = $plusManager ?? Di::_()->get('Plus\Manager');
        $this->commonSessionsManager = $commonSessionsManager ?? Di::_()->get('Sessions\CommonSessions\Manager');
        $this->password = $password ?? Di::_()->get('Security\Password');
        $this->demonetizationContext ??= Di::_()->get(DemonetizationContext::class);
        $this->demonetizePostStrategy ??= Di::_()->get(DemonetizePostStrategy::class);
        $this->demonetizePlusUserStrategy ??= Di::_()->get(DemonetizePlusUserStrategy::class);
        $this->boostManager ??= Di::_()->get(BoostManager::class);
        $this->activityPubReportDelegate ??= Di::_()->get(ActivityPubReportDelegate::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    public function onAction(Verdict $verdict)
    {
        if ($verdict->isAppeal() || !$verdict->isUpheld()) {
            error_log('Not upheld so no action');
            return; // Can not
        }

        $report = $verdict->getReport();

        // Disable ACL
        ACL::$ignore = true;
        $entityUrn = $verdict->getReport()->getEntityUrn();
        $entityGuid = $this->urn->setUrn($entityUrn)->getNss();

        $entity = $this->entitiesBuilder->single($entityGuid);

        $reportEntity = $verdict->getReport()->getEntity();
        // scope to only comments to reduce regression scope.
        if (!$entity && $reportEntity && $reportEntity instanceof Comment) {
            $entity = $reportEntity;
        }

        switch ($report->getReasonCode()) {
            case 1: // Illegal (not appealable)
                if ($entity->type !== 'user') {
                    $this->actions->setDeletedFlag($entity, true);
                    $this->saveAction->setEntity($entity)->save(isUpdate: true);
                }
                // Ban the owner of the post too
                $this->applyBan($report);
                break;
            case 2: // NSFW
                $nsfw = $report->getSubReasonCode();
                $entity->setNsfw(array_merge([$nsfw], $entity->getNsfw()));
                $entity->setNsfwLock(array_merge([$nsfw], $entity->getNsfwLock()));
                $this->saveAction->setEntity($entity)->save(isUpdate: true);
                // Apply a strike to the owner
                $this->applyStrike($report);

                // Was this post Minds+, if so we need to remove it from Minds+
                // We also removed
                if ($entity instanceof PaywallEntityInterface && $this->plusManager->isPlusEntity($entity)) {
                    $this->actions->setDeletedFlag($entity, true);
                    $this->saveAction->setEntity($entity)->save(isUpdate: true);
                }

                break;
            case 3: // Incites violence
                if ($entity->type !== 'user') {
                    $this->actions->setDeletedFlag($entity, true);
                    $this->saveAction->setEntity($entity)->save(isUpdate: true);
                }
                // Ban the owner of the post
                $this->applyBan($report);
                break;
            case 4:  // Harrasment
                if ($entity->type !== 'user') {
                    $this->actions->setDeletedFlag($entity, true);
                    $this->saveAction->setEntity($entity)->save(isUpdate: true);
                }
                // Apply a strike to the owner
                $this->applyStrike($report);
                break;
            case 5: // Personal and confidential information (not appelable)
                if ($entity->type !== 'user') {
                    $this->actions->setDeletedFlag($entity, true);
                    $this->saveAction->setEntity($entity)->save(isUpdate: true);
                }
                // Ban the owner of the post too
                $this->applyBan($report);
                break;
            case 7: // Impersonates (channel level)
                // Ban
                $this->applyBan($report);
                break;
            case 8: // Spam
                if ($entity->type !== 'user') {
                    $this->actions->setDeletedFlag($entity, true);
                    $this->saveAction->setEntity($entity)->save(isUpdate: true);

                    // Apply a strike to the owner
                    $this->applyStrike($report);
                } else {
                    // Apply a strike to the owner
                    $this->applyBan($report);
                }
                break;
            case 10: // Intellectual Property Violation.
                if ($entity->type === 'user') {
                    $this->applyBan($report);
                } else {
                    $this->actions->setDeletedFlag($entity, true);
                    $this->saveAction->setEntity($entity)->save(isUpdate: true);
                    $this->applyStrike($report);
                }
                break;
                //case 12: // Incorrect use of hashtags
                // De-index post
                // Apply a strike to the owner
                //    break;
            case 13: // Malware
                if ($entity->type !== 'user') {
                    $this->actions->setDeletedFlag($entity, true);
                    $this->saveAction->setEntity($entity)->save(isUpdate: true);
                }
                // Ban the owner
                $this->applyBan($report);
                break;
            case 14: // Strikes
                // Ban the user or make action

                switch ($report->getSubReason()) {
                    case 4: // Harrasment
                    case 8: // Spam
                    case 16: // Token manipulation
                        $this->applyBan($report);
                        break;
                    case 2.1: // NSFW
                    case 2.2:
                    case 2.3:
                    case 2.4:
                    case 2.5:
                    case 2.6:
                        $this->applyNsfwLock($report);
                        break;
                }

                break;
            case 16: // Token manipulation
                // Strike
                $this->applyBan($report);
                break;
            case 17: // Security
                $this->applyHackDefense($report);
                break;
            case 18: // Plus violation
                $this->demonetizationContext->withStrategy($this->demonetizePostStrategy)
                    ->execute($entity);
                $this->applyStrike($report);
                break;
        }

        // Enable ACL again
        ACL::$ignore = false;

        $this->rejectEntityBoosts($entity);
    }

    /**
     * Apply hacked account defense mechanism
     * @param Report $report
     * @return void
     */
    private function applyHackDefense(Report $report)
    {
        // Deactivate account
        $user = $this->entitiesBuilder->single($report->getEntityOwnerGuid());
        $user->enabled = 'no';

        $this->saveAction->setEntity($user)->withMutatedAttributes(['enabled'])->save(isUpdate: true);

        // Force change to random password
        $this->password->randomReset($user);

        // Destroy all sessions
        $this->commonSessionsManager->deleteAll($user);

        // Email user with reactivation instructions
        $this->emailDelegate->onHack($report);
    }

    /**
     * Apply a strike to the user
     * @param Report $report
     * @return void
     */
    private function applyStrike(Report $report)
    {
        $strike = new Strike;
        $strike->setReport($report)
            ->setReportUrn($report->getUrn())
            ->setUserGuid($report->getEntityOwnerGuid())
            ->setReasonCode($report->getReasonCode())
            ->setSubReasonCode($report->getSubReasonCode())
            ->setTimestamp($report->getTimestamp()); // Strike is recored for date of first report

        $count = $this->strikesManager->countStrikesInTimeWindow($strike, $this->strikesManager::STRIKE_TIME_WINDOW);

        if (!$count) {
            $this->strikesManager->add($strike);
        }

        // If 3 or more strikes, ban, demonetize for plus, or apply NSFW lock.
        if ($this->strikesManager->countStrikesInTimeWindow($strike, $this->strikesManager::STRIKE_RETENTION_WINDOW) >= 3) {
            if ($report->getReasonCode() === 2) {
                $this->applyNsfwLock($report);
            } elseif ($report->getReasonCode() === 18) {
                $entityOwner = $this->entitiesBuilder->single($report->getEntityOwnerGuid());
                $this->demonetizationContext->withStrategy($this->demonetizePlusUserStrategy)
                    ->execute($entityOwner);
            } else {
                $reasonCode = $report->getReasonCode();
                $subReasonCode = $report->getSubReasonCode();
                $report->setReasonCode(14) // Strike
                    ->setSubReasonCode(implode('.', [ $reasonCode, $subReasonCode ]));
                $this->applyBan($report);
            }
        } else {
            $this->reportToActivityPub($report);
        }
    }

    /**
     * Apply an NSFW lock to the user
     * @param Report $report
     */
    private function applyNsfwLock($report)
    {
        $user = $this->entitiesBuilder->single($report->getEntityOwnerGuid());

        //list($reason, $subReason) = explode('.', $report->getSubReason());
        $subReason = $report->getSubReasonCode();

        $user->setNsfw(array_merge($user->getNsfw(), [ $subReason ]));
        $user->setNsfwLock(array_merge($user->getNsfwLock(), [ $subReason ]));
        
        $this->saveAction->setEntity($user)->withMutatedAttributes(['nsfw', 'nsfw_lock'])->save(isUpdate: true);
    }

    /**
     * Apply a ban to the channel
     * @param Report $report
     */
    private function applyBan($report)
    {
        $user = $this->entitiesBuilder->single($report->getEntityOwnerGuid());

        $this->channelsBanManager
            ->setUser($user)
            ->ban(implode('.', [ $report->getReasonCode(), $report->getSubReasonCode() ]));

        $this->emailDelegate->onBan($report);
        $this->reportToActivityPub($report);
    }

    private function reportToActivityPub(Report $report): void
    {
        if (!$report->getEntity() instanceof FederatedEntityInterface) {
            return;
        }

        if ($report->getEntity()->getSource() !== FederatedEntitySourcesEnum::ACTIVITY_PUB) {
            return;
        }

        $this->activityPubReportDelegate->onReportUpheld($report);
    }

    /**
     * Reject running / pending boosts for a given entity.
     * @param mixed $entity - entity to reject boosts for.
     * @return bool - true on success.
     */
    private function rejectEntityBoosts(mixed $entity): bool
    {
        try {
            return $this->boostManager->forceRejectByEntityGuid(
                entityGuid: $entity->getGuid(),
                reason: BoostRejectionReason::REPORT_UPHELD,
                statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }
}
