<?php
/**
 * Minds Core Search Suggest API
 *
 * @version 1
 * @author Emiliano Balbuena
 */
namespace Minds\Controllers\api\v1\search;

use Minds\Core;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Search\Documents;

class suggest implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    public function get($pages)
    {
        if (!isset($_GET['q']) || !$_GET['q']) {
            return Factory::response([
              'status' => 'error',
              'message' => 'Missing query'
            ]);
        }

        (new Documents())->index(new \Minds\Entities\User('mark'));
        //(new Documents())->setupSuggestedMappings();
        $suggestions = (new Documents())->suggestQuery($_GET['q']);

        $response['suggestions'] = $suggestions['suggestion'][0]['options'];
        return Factory::response($response);
    }

    public function post($pages)
    {
        return Factory::response([]);
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
