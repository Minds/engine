<?php


namespace Minds\Controllers\api\v2\analytics;

use Minds\Api\Factory;
use Minds\Core\Analytics;
use Minds\Interfaces;

class views implements Interfaces\Api
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    public function post($pages)
    {
        $type = $pages[0];
        $identifier = $pages[1] ?? '';
        $clientMeta = $_POST['client_meta'] ?? [];

        $recordView = new Analytics\Views\Record();
        $recordView->setClientMeta($clientMeta)->setIdentifier($identifier);

        $response = ['status' => 'success'];

        if ($type == Analytics\Views\View::TYPE_BOOST) {
            $success = $recordView->recordBoost();
            if ($success) {
                $response = array_merge($response, $recordView->getBoostImpressionsData());
            } else {
                $response['status'] = 'error';
                $response['message'] = $recordView->getLastError();
            }
        } elseif ($type == Analytics\Views\View::TYPE_ACTIVITY || $type === Analytics\Views\View::TYPE_ENTITY) {
            $success = $recordView->recordEntity();

            if (!$success) {
                $response['status'] = 'error';
                $response['message'] = $recordView->getLastError();
            }
        }

        Factory::response($response);
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
