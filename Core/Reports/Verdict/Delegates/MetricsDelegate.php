<?php
/**
 * MetricsDelegate
 *
 * @author edgebal
 */

namespace Minds\Core\Reports\Verdict\Delegates;

use Exception;
use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Reports\Jury\Decision;
use Minds\Core\Reports\Verdict\Verdict;
use Minds\Entities\User;

class MetricsDelegate
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
    ) {
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
    }

    /**
     * @param Verdict $verdict
     * @throws Exception
     */
    public function onCast(Verdict $verdict)
    {
        if (!$verdict->isAppeal()) {
            return; // No need to record this
        }

        $decisions = $verdict->isAppeal() ?
            $verdict->getReport()->getAppealJuryDecisions() :
            $verdict->getReport()->getInitialJuryDecisions();

        $jurorGuids = array_map(function (Decision $decision) {
            return $decision->getJurorGuid();
        }, $decisions);

        foreach ($jurorGuids as $jurorGuid) {
            /** @var User */
            $juror = $this->entitiesBuilder->single($jurorGuid);

            $event = new Event();
            $event
                ->setType('action')
                ->setAction('jury_duty')
                ->setProduct('platform')
                ->setUserGuid($juror->guid)
                ->setUserPhoneNumberHash($juror->getPhoneNumberHash())
                ->setEntityGuid($verdict->getReport()->getUrn())
                ->push();
        }
    }
}
