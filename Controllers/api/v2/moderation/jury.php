<?php
/**
 * Api endpoint for jury duty
 */
namespace Minds\Controllers\api\v2\moderation;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Reports\Jury\Decision;
use Minds\Core\Reports\Jury\JuryClosedException;
use Minds\Core\Reports\Summons\SummonsNotFoundException;
use Minds\Core\Session;
use Minds\Interfaces;
use Zend\Diactoros\ServerRequestFactory;

class jury implements Interfaces\Api
{
    public function get($pages)
    {
        $juryType = $pages[0] ?? 'appeal';

        $config = Di::_()->get("Config");
        if (!$config->get("jury")['development_mode'] && ($juryType === 'appeal' || !Session::isAdmin())) {
            exit;
        }

        $juryManager = Di::_()->get('Moderation\Jury\Manager');
        $juryManager->setJuryType($juryType)
            ->setUser(Session::getLoggedInUser());

        if (isset($pages[1])) {
            $report = $juryManager->getReport($pages[1]);
            Factory::response([
                'report' => $report ? $report->export() : null,
            ]);
            return;
        }

        $reports = $juryManager->getUnmoderatedList([
            'limit' => $_GET['limit'] ?? 12,
            'offset' => $_GET['offset'] ?? '',
            'hydrate' => true,
        ]);

        $count = $juryManager->countList();

        Factory::response([
            'reports' => Factory::exportable($reports),
            'load-next' => $reports->getPagingToken(),
            'has-next' => !$reports->isLastPage(),
            'count' => $count,
        ]);
    }

    public function post($pages)
    {
        $request = ServerRequestFactory::fromGlobals();
        $juryType = $pages[0] ?? null;
        $urn = $pages[1] ?? null;

        $requestBody = $request->getParsedBody();
        
        $uphold = $requestBody['uphold'] ?? null;
        $adminReasonOverride = $requestBody['admin_reason_override'] ?? null;

        if (!$juryType) {
            Factory::response([
                'status' => 'error',
                'message' => 'You must supply the jury type in the URI like /:juryType/:entityGuid',
            ]);
            return;
        }
        
        if (!$urn) {
            Factory::response([
                'status' => 'error',
                'message' => 'You must supply the entity urn in the URI like /:juryType/:urn',
            ]);
            return;
        }

        if (!isset($uphold)) {
            Factory::response([
                'status' => 'error',
                'message' => 'uphold must be supplied in POST body',
            ]);
            return;
        }

        $loggedInUser = Session::getLoggedinUser();

        if (!$loggedInUser?->getPhoneNumberHash()) {
            Factory::response([
                'status' => 'error',
                'message' => 'juror must be in the rewards program',
            ]);
            return;
        }

        $juryManager = Di::_()->get('Moderation\Jury\Manager');
        $moderationManager = Di::_()->get('Moderation\Manager');
        $report = $moderationManager->getReport($urn);
        if ($juryType !== 'appeal') {
            $report->setAdminReasonOverride($adminReasonOverride);
        }

        $decision = new Decision();
        $decision
            ->setAppeal($juryType === 'appeal')
            ->setUphold($uphold)
            ->setReport($report)
            ->setTimestamp(time())
            ->setJurorGuid($loggedInUser->getGuid())
            ->setJurorHash($loggedInUser->getPhoneNumberHash());

        try {
            $juryManager->cast($decision);
        } catch (JuryClosedException $e) {
            Factory::response([
                'status' => 'error',
                'message' => 'The jury has already closed'
            ]);
            return;
        } catch (SummonsNotFoundException $e) {
            Factory::response([
                'status' => 'error',
                'message' => 'A summons could not be found'
            ]);
            return;
        } catch (\Exception $e) {
            Di::_()->get('Logger')->error($e);
            Factory::response([
                'status' => 'error',
                'message' => 'An unknown error has occurred'
            ]);
            return;
        }
        
        Factory::response([]);
    }

    public function put($pages)
    {
        Factory::response([]);
    }

    public function delete($pages)
    {
        Factory::response([]);
    }
}
