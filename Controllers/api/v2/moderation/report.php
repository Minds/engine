<?php
/**
 * Api endpoint to create a report
 */
namespace Minds\Controllers\api\v2\moderation;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\Reports;
use Minds\Core\Reports\Jury\Decision;
use Minds\Core\Session;
use Minds\Entities;
use Minds\Interfaces;

class report implements Interfaces\Api
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    public function post($pages): void
    {
        $user = Session::getLoggedInUser();

        if (!$user) {
            Factory::response([
                'status' => 'error',
                'message' => 'You must be logged into make a report',
            ]);
            return;
        }

        $manager = Di::_()->get('Moderation\UserReports\Manager');

        if (!isset($_POST['entity_guid']) && !isset($_POST['entity_urn'])) {
            Factory::response([
                'status' => 'error',
                'message' => 'Entity guid must be supplied',
            ]);
            return;
        }

        $entity = null;
        if ($_POST['entity_urn'] ?? false) {
            /**
             * @var EntitiesResolver $entitiesResolver
             */
            $entitiesResolver = Di::_()->get(EntitiesResolver::class);
            $entity = $entitiesResolver->single($_POST['entity_urn']);
            if (!$entity) {
                Factory::response([
                    'status' => 'error',
                    'message' => 'Entity not found',
                ]);
                return;
            }
        }

        // Gather the entity
        if (!$entity) {
            $entity = Entities\Factory::build($_POST['entity_guid']);
            if (!$entity) {
                Factory::response([
                    'status' => 'error',
                    'message' => 'Entity not found',
                ]);
                return;
            }
        }

        if (!isset($_POST['reason_code'])) {
            Factory::response([
                'status' => 'error',
                'message' => 'A reason code must be provided',
            ]);
            return;
        }

        $report = new Reports\Report();
        $report->setEntityUrn($entity->getUrn())
            ->setEntity($entity)
            ->setEntityOwnerGuid($entity->getOwnerGuid())
            ->setReasonCode((int) $_POST['reason_code'])
            ->setSubReasonCode($_POST['sub_reason_code'] ?? 0);

        $userReport = new Reports\UserReports\UserReport();
        $userReport
            ->setReport($report)
            ->setReporterGuid($user->getGuid())
            ->setTimestamp(time());

        if ($user->getPhoneNumberHash()) {
            $userReport->setReporterHash($user->getPhoneNumberHash());
        }

        if (!$manager->add($userReport)) {
            Factory::response([
                'status' => 'error',
                'message' => 'Report could not be saved',
            ]);
            return;
        }
        
        // Auto accept admin reports
        if ($user->isAdmin()) {
            $decision = new Decision();
            $decision->setAppeal(null)
                ->setAction('uphold')
                ->setUphold(true)
                ->setReport($report)
                ->setTimestamp(time())
                ->setJuror($user)
                ->setJurorGuid($user->getGuid())
                ->setJurorHash($user->getPhoneNumberHash());
            
            $juryManager = Di::_()->get('Moderation\Jury\Manager');
            $juryManager->cast($decision);
        }
        Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
