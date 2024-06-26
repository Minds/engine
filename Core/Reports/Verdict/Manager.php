<?php
/**
 * Verdict manager
 */
namespace Minds\Core\Reports\Verdict;

use Exception;
use Minds\Core\Reports\Report;
use Minds\Entities\User;

class Manager
{
    const INITIAL_JURY_SIZE = 1;
    const INITIAL_JURY_MAJORITY = 1;

    const APPEAL_JURY_SIZE = 12;
    const APPEAL_JURY_MAJORITY = 9;

    /** @var Repository $repository */
    private $repository;

    /** @var Delegates\ActionDelegate $actionDelegate */
    private $actionDelegate;

    /** @var Delegates\ReverseActionDelegate $reverseActionDelegate */
    private $reverseActionDelegate;

    /** @var Delegates\NotificationDelegate $notificationDelegate */
    private $notificationDelegate;

    /** @var Delegates\ReleaseSummonsesDelegate $releaseSummonsesDelegate */
    private $releaseSummonsesDelegate;

    /** @var Delegates\MetricsDelegate $metricsDelegate */
    private $metricsDelegate;

    public function __construct(
        $repository = null,
        $actionDelegate = null,
        $reverseActionDelegate = null,
        $notificationDelegate = null,
        $releaseSummonsesDelegate = null,
        $metricsDelegate = null
    ) {
        $this->repository = $repository ?: new Repository;
        $this->actionDelegate = $actionDelegate ?: new Delegates\ActionDelegate;
        $this->reverseActionDelegate = $reverseActionDelegate ?: new Delegates\ReverseActionDelegate;
        $this->notificationDelegate = $notificationDelegate ?: new Delegates\NotificationDelegate;
        $this->releaseSummonsesDelegate = $releaseSummonsesDelegate ?: new Delegates\ReleaseSummonsesDelegate;
        $this->metricsDelegate = $metricsDelegate ?: new Delegates\MetricsDelegate;
    }

    /**
     * @param array $opts
     * @return Response
     */
    public function getList($opts = [])
    {
        $opts = array_merge([
            'hydrate' => false,
        ], $opts);

        return $this->repository->getList($opts);
    }

    /**
     * @param long $entity_guid
     * @return Verdict
     */
    public function get($entity_guid)
    {
        return $this->repository->get($entity_guid);
    }

    /**
     * Run pending verdicts
     */
    public function run($juryType)
    {
        $verdicts = $this->repository->getList([
            'juryType' => $juryType,
        ]);

        foreach ($verdicts as $verdict) {
            $this->decide($verdict);
        }
       
        error_log('done');
    }

    /**
     * Run a single verdict
     * @param Report $report
     * @param User $juror
     * @return boolean
     */
    public function decideFromReport(
        Report $report,
        User $juror
    ): bool {
        $verdict = new Verdict();
        $verdict->setReport($report);
        return $this->decide(
            verdict: $verdict,
            juror: $juror
        );
    }

    /**
     * Decide on a verdict
     * @param Verdict $verdict
     * @param User|null $juror
     * @return boolean
     * @throws Exception
     */
    public function decide(
        Verdict $verdict,
        ?User $juror = null
    ): bool {
        $uphold = $this->isUpheld($verdict);
        $verdict->setUphold($uphold);
        $verdict->setTimestamp(time());

        if ($verdict->isUpheld() === null) {
            error_log("{$verdict->getReport()->getEntityUrn()} not actionable");
            return false;
        } else {
            error_log("{$verdict->getReport()->getEntityUrn()} decided with {$verdict->getAction()}");
            return $this->cast(
                verdict: $verdict,
                juror: $juror
            );
        }
    }

    /**
     * Cast a verdict
     * @param Verdict $verdict
     * @param User|null $juror
     * @return boolean
     * @throws Exception
     */
    public function cast(
        Verdict $verdict,
        ?User $juror = null
    ): bool {
        $added = $this->repository->add($verdict);
        
        // Make the action
        $this->actionDelegate->onAction(
            verdict: $verdict,
            juror: $juror
        );

        // Reverse the action (if appeal)
        $this->reverseActionDelegate->onReverse($verdict);

        // Save metrics (for contributions)
        $this->metricsDelegate->onCast($verdict);

        // Send a notification to the reported user
        $this->notificationDelegate->onAction($verdict);

        // Release summonses
        $this->releaseSummonsesDelegate->onCast($verdict);

        // Send rewards to reporters

        return $added;
    }

    /**
     * Reach a verdict
     * @param Verdict $verdict
     * @return string
     */
    public function isUpheld(Verdict $verdict)
    {
        $upholdCount = 0;
        $overturnCount = 0;
        $totalCount = 0;
        $jurySize = $verdict->getReport()->isAppeal() ? static::APPEAL_JURY_SIZE : static::INITIAL_JURY_SIZE;
        $overturnCountRequired = $verdict->getReport()->isAppeal() ? static::APPEAL_JURY_MAJORITY : static::INITIAL_JURY_MAJORITY;

        foreach ($verdict->getDecisions() as $decision) {
            $totalCount++;

            if ($decision->isUpheld()) {
                ++$upholdCount;
            } else {
                ++$overturnCount;
            }
        }

        if ($totalCount < $jurySize) {
            return null; // not ready yet
        }

        if ($overturnCount >= $overturnCountRequired) {
            return false;
        }

        return true;
    }
}
