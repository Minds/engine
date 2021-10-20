<?php
/**
 * Experiments manager
 */
namespace Minds\Core\Experiments;

use Minds\Entities\User;
use Growthbook;

class Manager
{
    /** @var Growthbook\Client */
    private $growthbook;

    /** @var Growthbook\User */
    private $growthbookUser;

    /** @var Growthbook\Experiment[] */
    private $experiments;

    public function __construct(Growthbook\Client $growthbook = null)
    {
        $this->growthbook = $growthbook ?? new Growthbook\Client();

        $this->experiments = [
            new Growthbook\Experiment("channel-gallery", ["on", "off"]),
            new Growthbook\Experiment("boost-prompt", ["on", "off"]),
            new Growthbook\Experiment("top-feed", ["off", "on"]),
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
            'id' => $user ? $user->getGuid() : uniqid('exp-', true),
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
}
