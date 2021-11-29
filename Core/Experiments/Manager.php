<?php
/**
 * Experiments manager
 */
namespace Minds\Core\Experiments;

use Minds\Entities\User;
use Growthbook;
use Minds\Core\Di\Di;
use Minds\Core\Experiments\Cookie\Manager as CookieManager;

class Manager
{
    /** @var Growthbook\Client */
    private $growthbook;

    /** @var Growthbook\User */
    private $growthbookUser;

    /** @var Growthbook\Experiment[] */
    private $experiments;

    /** @var CookieManager */
    private $cookieManager;

    public function __construct(Growthbook\Client $growthbook = null, CookieManager $cookieManager = null)
    {
        $this->growthbook = $growthbook ?? new Growthbook\Client();
        $this->cookieManager = $cookieManager ?? Di::_()->get('Experiments\Cookie\Manager');

        $this->experiments = [
            new Growthbook\Experiment("channel-gallery", ["on", "off"]),
            new Growthbook\Experiment("boost-rotator", ["on", "off"]),
            new Growthbook\Experiment("boost-prompt", ["on", "off"]),
            new Growthbook\Experiment("discovery-homepage", ["off", "on"]),
            new Growthbook\Experiment("top-feed-2", ["on", "off"]),
        ];
    }

    /**
     * Set the user who will view experiments
     * @param User $user (optional)
     * @return Manager
     */
    public function setUser(User $user = null)
    {
        $this->growthbookUser = $this->growthbook->user([
            'id' => $this->getUserId($user)
        ]);
        return $this;
    }

    /**
     * Return a list of experiments
     * @return Growthbook\TrackData<mixed>[]
     */
    public function getExperiments(): array
    {
        foreach ($this->experiments as $experiment) {
            $this->growthbookUser->experiment($experiment);
        }
        return $this->growthbook->getTrackData();
    }

    /**
     * @return array
     */
    public function getExportableExperiments(): array
    {
        return array_map(function ($trackData) {
            return [
                'experimentId' => $trackData->experiment->key,
                'variationId' => $trackData->result->variationId,
                'variations' => $trackData->experiment->variations
            ];
        }, $this->getExperiments());
    }
    
    /**
     * Gets User ID for experiments. Will get either the users experimentsId via cookie,
     * their logged in user GUID, or generate a unique ID and store it in a cookie.
     * @param User $user - user to generate / get ID for - nullable.
     * @return string user id for experiments.
     */
    private function getUserId(User $user = null): string
    {
        // if cookie exists, return it's value as the id.
        $experimentsIdCookie = $this->cookieManager->get();

        if ($experimentsIdCookie) {
            return $experimentsIdCookie;
        }

        // else if user is logged in, use their userGuid.
        if ($user) {
            return $user->getGuid();
        }

        // else if no user - generate an ID, store it in a cookie.
        $id = uniqid('exp-', true);
        $this->cookieManager->set($id);
        return $id;
    }
}
