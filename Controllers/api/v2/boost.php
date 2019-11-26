<?php

namespace Minds\Controllers\api\v2;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Core\Boost\Network;

class boost implements Interfaces\Api
{
    private $rate = 1;

    /**
     * Return impressions for a request
     * @param array $pages
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        $response = [];
        $limit = isset($_GET['limit']) && $_GET['limit'] ? (int)$_GET['limit'] : 12;
        $offset = isset($_GET['offset']) && $_GET['offset'] ? $_GET['offset'] : '';
        $config = (array) Core\Di\Di::_()->get('Config')->get('boost');

        switch ($pages[0]) {
            case is_numeric($pages[0]):
                $review = new Core\Boost\Peer\Review();
                $boost = $review->getBoostEntity($pages[0]);
                if ($boost->getState() != 'created') {
                    Factory::response(['status' => 'error', 'message' => 'entity not in boost queue']);
                    return;
                }
                $response['entity'] = $boost->getEntity()->export();
                $response['bid'] = $boost->getBid();
                break;

            case "rates":
                $response['hasPaymentMethod'] = false;
                $response['rate'] = $this->rate;
                $response['cap'] = $config['network']['max'];
                $response['min'] = $config['network']['min'];
                $response['priority'] = $this->getQueuePriorityRate();
                $response['usd'] = $this->getUSDRate();
                $response['minUsd'] = $this->getMinUSDCharge();
                $response['tokens'] = $this->getTokensRate();
                break;

            case "p2p":
                $review = new Core\Boost\Peer\Review();
                $review->setType(Core\Session::getLoggedInUser()->guid);
                $boosts = $review->getReviewQueue($limit, $offset);
                $boost_entities = [];
                /** @var $boost Core\Boost\Network\Boost */
                foreach ($boosts['data'] as $i => $boost) {
                    if ($boost->getState() != 'created') {
                        unset($boosts[$i]);
                        continue;
                    }

                    $boost_entities[$i] = $boost->getEntity();
                    $boost_entities[$i]->guid = $boost->getGuid();
                    $boost_entities[$i]->points = $boost->getBid();
                }

                $response['boosts'] = factory::exportable($boost_entities, ['points']);
                $response['load-next'] = $boosts['next'];
                break;

            case "newsfeed":
            case "content":
                $review = new Core\Boost\Network\Review();
                $review->setType($pages[0]);
                $boosts = $review->getOutbox(Core\Session::getLoggedinUser()->guid, $limit, $offset);
                $response['boosts'] = Factory::exportable($boosts['data']);
                $response['load-next'] = $boosts['next'];
                break;
        }

        if (isset($response['boosts']) && $response['boosts']) {
            if ($response['boosts'] && !isset($response['load-next'])) {
                $response['load-next'] = end($response['boosts'])['guid'];
            }
        }

        Factory::response($response);
    }

    /**
     * Boost an entity
     * @param array $pages
     * @return void
     *
     * API:: /v2/boost/:type/:guid
     */
    public function post($pages)
    {
        Factory::isLoggedIn();

        if (!isset($pages[0])) {
            Factory::response(['status' => 'error', 'message' => ':type must be passed in uri']);
            return;
        }

        if (!isset($pages[1])) {
            Factory::response(['status' => 'error', 'message' => ':guid must be passed in uri']);
            return;
        }

        $impressions = (int) $_POST['impressions'];

        if (!isset($impressions)) {
            Factory::response(['status' => 'error', 'message' => 'impressions must be sent in post body']);
            return;
        }

        if ($impressions <= 0) {
            Factory::response(['status' => 'error', 'message' => 'impressions must be a positive whole number']);
            return;
        }

        $paymentMethod = isset($_POST['paymentMethod']) ? $_POST['paymentMethod'] : [];
        $config = (array) Core\Di\Di::_()->get('Config')->get('boost');

        if ($paymentMethod['method'] === 'onchain') {
            $config['network']['max'] *= 2;
        }

        if ($impressions < $config['network']['min'] || $impressions > $config['network']['max']) {
            Factory::response([
                'status' => 'error',
                'message' => "You must boost between {$config['network']['min']} and {$config['network']['max']} impressions"
            ]);
            return;
        }

        $response = [];
        $entity = Entities\Factory::build($pages[1]);

        if (!$entity) {
            Factory::response(['status' => 'error', 'message' => 'entity not found']);
            return;
        }

        if ($pages[0] == "object" || $pages[0] == "user" || $pages[0] == "suggested" || $pages[0] == 'group') {
            $pages[0] = "content";
        }

        if ($pages[0] == "activity") {
            $pages[0] = "newsfeed";
        }

        try {
            switch (ucfirst($pages[0])) {
                case "Newsfeed":
                case "Content":
                    $priority = false;
                    $priorityRate = 0;

                    if (isset($_POST['priority']) && $_POST['priority']) {
                        $priority = true;
                        $priorityRate = $this->getQueuePriorityRate();
                    }

                    $bidType = isset($_POST['bidType']) ? $_POST['bidType'] : null;
                    $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
                    $checksum =  isset($_POST['checksum']) ? $_POST['checksum'] : '';

                    $amount = $impressions / $this->rate;
                    if ($priority) {
                        $amount *= $priorityRate + 1;
                    }

                    if (!in_array($bidType, [ 'usd', 'tokens' ], true)) {
                        Factory::response([
                            'status' => 'error',
                            'stage' => 'initial',
                            'message' => 'Unknown currency'
                        ]);
                        return;
                    }

                    // Amount normalizing

                    switch ($bidType) {
                        case 'usd':
                            $amount = round($amount / $this->getUSDRate(), 2) * 100;

                            if (($amount / 100) < $this->getMinUSDCharge()) {
                                Factory::response([
                                    'status' => 'error',
                                    'message' => 'You must spend at least $' . $this->getMinUSDCharge()
                                ]);

                                return;
                            }
                            break;

                        case 'tokens':
                            $amount = BigNumber::toPlain(round($amount / $this->getTokensRate(), 4), 18);
                            break;
                    }

                    // Categories

                    $validCategories = array_keys(Di::_()->get('Config')->get('categories') ?: []);
                    if (!is_array($categories)) {
                        $categories = [$categories];
                    }

                    foreach ($categories as $category) {
                        if (!in_array($category, $validCategories, true)) {
                            Factory::response([
                                'status' => 'error',
                                'message' => 'Invalid category ID: ' . $category
                            ]);
                            return;
                        }
                    }

                    // Validate entity

                    $boostHandler = Core\Boost\Handler\Factory::get($pages[0]);
                    $isEntityValid = $boostHandler->validateEntity($entity);

                    if (!$isEntityValid) {
                        Factory::response([
                            'status' => 'error',
                            'message' => 'Entity cannot be boosted'
                        ]);
                        return;
                    }

                    // Generate Boost entity

                    $state = 'created';

                    if ($bidType == 'tokens' && $paymentMethod['method'] === 'onchain') {
                        $state = 'pending';
                    }

                    /** @var Network\Manager $manager */
                    $manager = Di::_()->get('Boost\Network\Manager');

                    $boost = (new Network\Boost())
                        ->setEntityGuid($entity->getGuid())
                        ->setEntity($entity)
                        ->setBid($amount)
                        ->setBidType($bidType)
                        ->setImpressions($impressions)
                        ->setOwnerGuid(Core\Session::getLoggedInUser()->getGuid())
                        ->setOwner(Core\Session::getLoggedInUser())
                        ->setCreatedTimestamp(round(microtime(true) * 1000))
                        ->setType(lcfirst($pages[0]))
                        ->setPriority(false);

                    if ($manager->checkExisting($boost)) {
                        Factory::response([
                            'status' => 'error',
                            'message' => "There's already an ongoing boost for this entity"
                        ]);
                        return;
                    }

                    if ($manager->isBoostLimitExceededBy($boost)) {
                        $maxDaily = Di::_()->get('Config')->get('max_daily_boost_views') / 1000;
                        Factory::response([
                            'status' => 'error',
                            'message' => "Exceeded maximum of ".$maxDaily." offchain tokens per 24 hours."
                        ]);
                        return;
                    }

                    // Pre-set GUID

                    if ($bidType == 'tokens' && isset($_POST['guid'])) {
                        $guid = $_POST['guid'];

                        if (!is_numeric($guid) || $guid < 1) {
                            Factory::response([
                                'status' => 'error',
                                'stage' => 'transaction',
                                'message' => 'Provided GUID is invalid'
                            ]);
                            return;
                        }

                        $existingBoost = $manager->get("urn:boost:{$boost->getType()}:{$guid}");

                        if ($existingBoost) {
                            Factory::response([
                                'status' => 'error',
                                'stage' => 'transaction',
                                'message' => 'Provided GUID already exists'
                            ]);
                            return;
                        }

                        $boost->setGuid($guid);

                        $calculatedChecksum = (new Core\Boost\Checksum())
                            ->setGuid($guid)
                            ->setEntity($entity)
                            ->generate();

                        if ($checksum !== $calculatedChecksum) {
                            Factory::response([
                                'status' => 'error',
                                'stage' => 'transaction',
                                'message' => 'Checksum does not match. Expected: ' . $calculatedChecksum
                            ]);
                            return;
                        }
                        $boost->setChecksum($checksum);
                    }

                    // Payment

                    if (isset($_POST['newUserPromo']) && $_POST['newUserPromo'] && $impressions == 200 && !$priority) {
                        $transactionId = "free";
                    } else {
                        /** @var Core\Boost\Payment $payment */
                        $payment = Di::_()->get('Boost\Payment');
                        $transactionId = $payment->pay($boost, $paymentMethod);
                    }

                    // Run boost
    
                    $boost->setTransactionId($transactionId);
                    $success = $manager->add($boost);

                    if (!$success) {
                        $response['status'] = 'error';
                    }
                    break;

                default:
                    $response['status'] = 'error';
                    $response['message'] = "boost handler not found";
            }
        } catch (\Exception $e) {
            Factory::response([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            return;
        }

        Factory::response($response);
    }

    /**
     * @param array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        Factory::response([]);
    }

    /**
     * Called when a network boost is revoked
     * @param array $pages
     */
    public function delete($pages)
    {
        Factory::isLoggedIn();

        $response = [];

        $type = $pages[0];
        $guid = $pages[1];
        $action = $pages[2];

        if (!$guid) {
            Factory::response([
                'status' => 'error',
                'message' => 'We couldn\'t find that boost'
            ]);
            return;
        }

        if (!$action) {
            Factory::response([
                'status' => 'error',
                'message' => "You must provide an action: revoke"
            ]);
            return;
        }

        /** @var Core\Boost\Network\Review|Core\Boost\Peer\Review $review */
        $review = $type == 'peer' ? new Core\Boost\Peer\Review() : new Core\Boost\Network\Review();
        $review->setType($type);
        $boost = $review->getBoostEntity($guid);
        if (!$boost) {
            Factory::response([
                'status' => 'error',
                'message' => 'Boost not found'
            ]);
            return;
        }

        if ($boost->getOwner()->guid != Core\Session::getLoggedInUserGuid()) {
            Factory::response([
                'status' => 'error',
                'message' => 'You cannot revoke that boost'
            ]);
            return;
        }

        if ($boost->getState() != 'created') {
            Factory::response([
                'status' => 'error',
                'message' => 'This boost is in the ' . $boost->getState() . ' state and cannot be refunded'
            ]);
            return;
        }

        if ($action == 'revoke') {
            $review->setBoost($boost);
            try {
                $success = $review->revoke();

                if ($success) {
                    /** @var Core\Boost\Payment $payment */
                    $payment = Di::_()->get('Boost\Payment');
                    $payment->refund($boost);
                } else {
                    $response['status'] = 'error';
                }
            } catch (\Exception $e) {
                $response['status'] = $e->getMessage();
            }
        }

        Factory::response($response);
        return;
    }

    protected function getQueuePriorityRate()
    {
        // @todo: Calculate based on boost queue
        return 10;
    }

    protected function getUSDRate()
    {
        $config = (array)Core\Di\Di::_()->get('Config')->get('boost');

        return isset($config['usd']) ? $config['usd'] : 1000;
    }

    protected function getTokensRate()
    {
        return Core\Di\Di::_()->get('Blockchain\Manager')->getRate();
    }

    protected function getMinUSDCharge()
    {
        return 1.00;
    }
}
