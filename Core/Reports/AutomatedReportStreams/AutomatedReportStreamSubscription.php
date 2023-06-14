<?php
namespace Minds\Core\Reports\AutomatedReportStreams;

use Minds\Common\SystemUser;
use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Reports;
use Minds\Core\Security\ACL;

class AutomatedReportStreamSubscription implements SubscriptionInterface
{
    public function __construct(
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Entities\Resolver $entitiesResolver = null,
        protected ?Reports\UserReports\Manager $userReportsManager = null,
        protected ?Logger $logger = null,
        protected ?ACL $acl = null,
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->entitiesResolver ??= new Entities\Resolver();
        $this->userReportsManager ??= Di::_()->get('Moderation\UserReports\Manager');
        $this->logger ??= Di::_()->get('Logger');
        $this->acl ??= Di::_()->get('Security\ACL');
    }

    /**
     * @inheritDoc
     */
    public function getTopic(): TopicInterface
    {
        return new AdminReportsTopic();
    }

    /**
     * @inheritDoc
     */
    public function getTopicRegex(): string
    {
        $topics = [
            // Events\AdminReportSpamCommentEvent::TOPIC_NAME,
            Events\AdminReportSpamCommentEvent::TOPIC_NAME,
            Events\AdminReportSpamAccountEvent::TOPIC_NAME,
            Events\AdminReportTokenAccountEvent::TOPIC_NAME,
        ];
        return '(' . implode('|', $topics) . ')';
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionId(): string
    {
        return 'auto-reports';
    }

    /**
     * @inheritDoc
     */
    public function consume(EventInterface $event): bool
    {
        // Do not ignore the ACL
        $this->acl->setIgnore(false);

        $report = new Reports\Report();
        $entity = null;

        /**
         * Map the report reasons
         */
        switch ($event::class) {
            case Events\ScoreCommentsForSpamEvent::class:
                /** @var Events\ScoreCommentsForSpamEvent */
                $event = $event;
                if ($event->getSpamScore() < 0.9) {
                    $this->logger->info("{$event->getCommentUrn()} is not flagged as spam. Awknowledging.");
                    return true;
                }
                $report->setReasonCode(8);
                $report->setSubReasonCode(0);
                break;
            case Events\AdminReportSpamCommentEvent::class:
            case Events\AdminReportSpamAccountEvent::class:
                $report->setReasonCode(8);
                $report->setSubReasonCode(0);
                break;
            case Events\AdminReportTokenAccountEvent::class:
                $report->setReasonCode(16);
                $report->setSubReasonCode(0);
                break;
        }

        /**
         * Set the entity to report
         */
        switch ($event::class) {
            case Events\ScoreCommentsForSpamEvent::class:
            case Events\AdminReportSpamCommentEvent::class:
                /** @var Events\ScoreCommentsForSpamEvent|Events\AdminReportSpamCommentEvent */
                $event = $event;
    
                $entity = $this->entitiesResolver->single(new Urn($event->getCommentUrn()));
                break;
            case Events\AdminReportSpamAccountEvent::class:
            case Events\AdminReportTokenAccountEvent::class:
                /** @var Events\AdminReportSpamAccountEvent|Events\AdminReportTokenAccountEvent */
                $event = $event;
                $entity = $this->entitiesBuilder->single($event->getUserGuid());
                break;
            default:
                $this->logger->error("Unsupported event received by subscription. Not awknowledging.");
                return false; // Unsupported at the moment. Do not awknowledge
        }
  
        if (!$entity) {
            $this->logger->warning("Unable to find entity. Awknowledging.");
            return true; // No entity found, but return true to awknowledge
        }

        if (!$this->acl->read($entity)) {
            $this->logger->warning("Unable to read entity ({$entity->getUrn()} so we will not report. Awknowledging.");
            return true; // No entity found, but return true to awknowledge
        }

        $report->setEntity($entity);
        $report->setEntityOwnerGuid($entity->getOwnerGuid());
        $report->setEntityUrn($entity->getUrn());

        /**
         * Use SystemUser (ie. @minds) for the reports
         */
        $userReport = new Reports\UserReports\UserReport();
        $userReport
            ->setReport($report)
            ->setReporterGuid(SystemUser::GUID)
            ->setTimestamp(time());

        /**
         * Submit report to admins
         */
        if ($this->userReportsManager->add($userReport)) {
            $this->logger->info("Submitted report {$report->getEntityUrn()} {$report->getReasonCode()}:{$report->getSubReasonCode()}");
            return true;
        } else {
            $this->logger->error("Unable to submit user report", [
                'userReport' => print_r($userReport),
            ]);
        }

        return false;
    }
}
