<?php

namespace Minds\Controllers\api\v2\boost;

use Minds\Api\Exportable;
use Minds\Core;
use Minds\Entities\User;
use Minds\Interfaces;
use Minds\Api\Factory;

class feed implements Interfaces\Api
{
    /** @var User */
    protected $currentUser;
    /** @var array */
    protected $boosts = [];
    protected $next;

    protected $type;
    protected $limit;
    protected $offset;
    protected $rating;
    protected $platform;
    protected $quality = 0;
    protected $isBoostFeed;

    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws \Exception
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        $this->currentUser = Core\Session::getLoggedinUser();

        if (!$this->parseAndValidateParams() || !$this->validBoostUser()) {
            $this->sendResponse();
            return;
        }

        $this->type = $pages[0] ?? 'newsfeed';

        if ($this->isBoostFeed) {
            $this->offset = $_GET['from_timestamp'] ?? 0;
        }

        switch ($this->type) {
            case 'newsfeed':
                $this->getBoosts();
                break;
            default:
                $this->sendError('Unsupported boost type');
                return;
        }

        $this->sendResponse();
    }

    protected function parseAndValidateParams(): bool
    {
        $this->limit = abs(intval($_GET['limit'] ?? 2));
        $this->offset = $_GET['offset'] ?? 0;
        $this->rating = intval($_GET['rating'] ?? $this->currentUser->getBoostRating());
        $this->platform = $_GET['platform'] ?? 'other';
        $this->isBoostFeed = $_GET['boostfeed'] ?? false;

        if ($this->limit === 0) {
            return false;
        }

        if ($this->limit > 500) {
            $this->limit = 500;
        }

        return true;
    }

    protected function validBoostUser(): bool
    {
        return !($this->currentUser->disabled_boost && $this->currentUser->isPlus());
    }

    protected function sendResponse(): void
    {
        $boosts = empty($this->boosts) ? [] : Exportable::_($this->boosts);
        Factory::response([
            'entities' => $boosts,
            'load-next' => $this->next,
        ]);
    }

    protected function sendError(string $message): void
    {
        Factory::response([
            'status' => 'error',
            'message' => $message
        ]);
    }

    protected function getBoosts()
    {
        $feed = new Core\Boost\Feeds\Boost($this->currentUser);
        $this->boosts = $feed->setLimit($this->limit)
            ->setOffset($this->offset)
            ->setRating($this->rating)
            ->setPlatform($this->platform)
            ->setQuality($this->quality)
            ->get();
        $this->next = $feed->getOffset();
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
