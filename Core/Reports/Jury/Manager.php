<?php
/**
 * Jury manager
 */
namespace Minds\Core\Reports\Jury;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\Reports\Summons\SummonsNotFoundException;
use Minds\Core\Reports\Summons\Summons as SummonsEntity;
use Minds\Core\Security\ACL;
use Minds\Core\Analytics\Metrics\Event as AnalyticsEvent;
use Minds\Entities\User;
use Minds\Core\Reports\Report;

class Manager
{
    /** @var Repository $repository */
    private $repository;

    /** @var EntitiesBuilder $entitiesResolver */
    private $entitiesResolver;

    /** @var VerdictManager $verdictManager */
    private $verdictManager;

    /** @var SummonsManager $summonsManager */
    private $summonsManager;

    /** @var ACL $acl */
    private $acl;

    /** @var AnalyticsEvent $analyticsEvent */
    private $analyticsEvent;

    /** @var Logger $analyticsEvent */
    private $logger;

    /** @var string $juryType */
    private $juryType;

    /** @var User $user */
    private $user;

    public function __construct(
        $repository = null,
        $entitiesResolver = null,
        $verdictManager = null,
        $summonsManager = null,
        $acl = null,
        $analyticsEvent = null,
        $logger = null
    ) {
        $this->repository = $repository ?: new Repository;
        $this->entitiesResolver = $entitiesResolver  ?: new EntitiesResolver;
        $this->verdictManager = $verdictManager ?: Di::_()->get('Moderation\Verdict\Manager');
        $this->summonsManager = $summonsManager ?: Di::_()->get('Moderation\Summons\Manager');
        $this->acl = $acl ?: new ACL;
        $this->analyticsEvent = $analyticsEvent ?: new AnalyticsEvent();
        $this->logger = $logger ?: Di::_()->get('Logger');
    }

    /**
     * Set the jury type
     * @param string $juryType
     * @return $this
     */
    public function setJuryType($juryType)
    {
        $this->juryType = $juryType;
        return $this;
    }

    /**
     * Set the session user
     * @param User $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
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
     * @param array $opts
     * @return Response
     */
    public function getUnmoderatedList($opts = [])
    {
        $opts = array_merge([
            'hydrate' => false,
            'juryType' => $this->juryType,
            'user' => $this->user, // Session user
            'limit' => 12,
            'offset' => '',
        ], $opts);

        $response = $this->repository->getList($opts);

        if ($opts['hydrate']) {
            foreach ($response as $report) {
                $entity = $this->entitiesResolver->single(
                    (new Urn())->setUrn($report->getEntityUrn())
                );
                $report->setEntity($entity);
            }
        }

        return $response;
    }

    /**
     * Counts entries in a list, filtered by class-level juryType.
     * @param array $opts - 'juryType'
     * @return Response
     */
    public function countList($opts = [])
    {
        $opts = array_merge([
            'juryType' => $this->juryType,
        ], $opts);

        $response = $this->repository->count($opts);

        return $response;
    }

    /**
     * Return a single report
     * @param string $urn
     * @return Report
     */
    public function getReport($urn)
    {
        $report = $this->repository->get($urn);
        if ($report) {
            $ignore = $this->acl->setIgnore(true);
            $entity = $this->entitiesResolver->single(
                (new Urn())->setUrn($report->getEntityUrn())
            );
            $this->acl->setIgnore($ignore);
            $report->setEntity($entity);
        }
        return $report;
    }

    /**
     * Cast a decision
     * @param Decision $decision
     * @return boolean
     */
    public function cast(Decision $decision)
    {
        $report = $decision->getReport();

        $isAdmin = Core\Session::isAdmin();

        if (!in_array($report->getState(), [ 'reported', 'appealed' ], true)) {
            // report exception if not admin.
            if (!$isAdmin) {
                throw new JuryClosedException();
            }
            // if an admin - mark it as decided so that it no longer appears in queue.
            $report->setState('initial_jury_decided');
        }

        if ($decision->isAppeal() && !$this->hasSummons($decision)) {
            throw new SummonsNotFoundException();
        }

        $success = $this->repository->add($decision);

        if ($decision->isAppeal()) {
            $decisions = $report->getAppealJuryDecisions();
            $decisions[] = $decision;
            $report->setAppealJuryDecisions($decisions);
        } else {
            $decisions = $report->getInitialJuryDecisions();
            $decisions[] = $decision;
            $report->setInitialJuryDecisions($decisions);
        }

        $this->verdictManager->decideFromReport($report);

        // Record jury votes for non-admins.
        if (!$isAdmin) {
            $this->pushAnalyticsEvent($report);
        }

        return $success;
    }

    /**
     * Return if a summons exists
     * @param Decision $decision
     * @return boolean
     */
    private function hasSummons(Decision $decision)
    {
        $summons = new SummonsEntity();
        $summons->setReportUrn($decision->getReport()->getUrn())
            ->setJurorGuid($decision->getJurorGuid())
            ->setJuryType('appeal_jury');
        return $this->summonsManager->isSummoned($summons);
    }

    /**
     * Push an analytics event for a cast vote.
     * @param Report $report - the report to push analytics for.
     * @return void
     */
    private function pushAnalyticsEvent(Report $report): void
    {
        try {
            $action = $report->isUpheld() ? 'upheld' : 'overturned';

            $this->analyticsEvent->setUserGuid($this->user->getGuid())
                ->setType('action')
                ->setAction('jury_vote_'.$action)
                ->setEntityGuid($report->getEntity()->getGuid())
                ->setUserPhoneNumberHash($this->user->getPhoneNumberHash() ?? '')
                ->push();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
