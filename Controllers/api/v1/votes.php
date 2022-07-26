<?php
/**
 * Minds Votes API (formerly known as thumbs)
 *
 * @author emi
 */

namespace Minds\Controllers\api\v1;

use Exception;
use Minds\Api\Factory;
use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\InvalidSolutionException;
use Minds\Core\Di\Di;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Session;
use Minds\Core\Votes\Counters;
use Minds\Core\Votes\Manager;
use Minds\Core\Votes\Vote;
use Minds\Interfaces;
use Zend\Diactoros\ServerRequestFactory;

class votes implements Interfaces\Api
{
    public function __construct(
        private ?ExperimentsManager $experimentsManager = null
    ) {
        // $this->experimentsManager ??= Di::_()->get("Experiments\Manager");
    }

    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        if (!isset($pages[0]) || !$pages[0]) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid entity GUID',
            ]);
        }

        $direction = isset($pages[1]) ? $pages[1] : 'up';
        $count = 0;

        try {
            /** @var Counters $counters */
            $counters = Di::_()->get('Votes\Counters');
            $count = $counters->get($pages[0], $direction);
        } catch (Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return Factory::response([
            'count' => $count,
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return $this->put($pages);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     * @return mixed|null
     * @throws UnverifiedEmailException
     * @throws Exception
     */
    public function put($pages)
    {
        if (!isset($pages[0]) || !$pages[0]) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid entity GUID',
            ]);
        }

        $direction = isset($pages[1]) ? $pages[1] : 'up';

        $loggedInUser = Session::getLoggedinUser();


        /** @var Manager $manager */
        $manager = new Manager();
        $manager
            ->setUser($loggedInUser);

        $vote = new Vote();

        $vote->setEntity($pages[0])
            ->setDirection($direction)
            ->setActor($loggedInUser);

        $options = [
            'puzzleSolution' => ''
        ];

        $request = ServerRequestFactory::fromGlobals();
        $requestBody = json_decode($request->getBody()->getContents(), true);

        $experimentsManager = (new ExperimentsManager())
            ->setUser($loggedInUser);

        if ($experimentsManager->isOn("minds-3119-captcha-for-engagement") && !$manager->has($vote)) {
            $puzzleSolution = $requestBody['puzzle_solution'] ?? '';
            $options['puzzleSolution'] = $puzzleSolution;
        }

        try {
            $manager->toggle($vote, $options);
        } catch (UnverifiedEmailException $e) {
            throw $e;
        } catch (InvalidSolutionException $e) {
            // return Factory::response([
            //     'status' => 'error',
            //     'message' => "This engagement looks like spam",
            // ]);
        } catch (Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        if (!isset($pages[0]) || !$pages[0]) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid entity GUID',
            ]);
        }

        $direction = isset($pages[1]) ? $pages[1] : 'up';

        try {
            $vote = new Vote();
            $vote->setEntity($pages[0])
                ->setDirection($direction)
                ->setActor(Session::getLoggedinUser());

            /** @var Manager $manager */
            $manager = Di::_()->get('Votes\Manager');
            $manager->cancel($vote);
        } catch (UnverifiedEmailException $e) {
            throw $e;
        } catch (Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return Factory::response([]);
    }
}
