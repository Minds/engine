<?php
namespace Minds\Core\Reports\AutomatedReportStreams;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Reports\Report;

class AutomatedReportStreamSubscription implements SubscriptionInterface
{
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
        return '(spam-comments|spam-accounts|token-accounts)';
    }

    public function getSubscriptionId(): string
    {
        return 'auto-reports';
    }

    /**
     * @inheritDoc
     */
    public function consume(EventInterface $event): bool
    {
        var_dump($event);

        // We have an event, what is the report type?

        // Build out a report item
        // $report = new Report();
        // $report->setEntity();
        // $report->setReasonCode(8);
        // $report->setSubReasonCode(0);

        // $userReport = new Reports\UserReports\UserReport();
        // $userReport
        //     ->setReport($report)
        //     ->setReporterGuid($user->getGuid())
        //     ->setTimestamp(time());

        // Submit report to admins

        return true;
    }
}
