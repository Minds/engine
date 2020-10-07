<?php
namespace Minds\Core\Permaweb\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Permaweb\Manager;

/**
 * Abstract AbstractPermawebDelegate
 * @method __construct()
 * @method dispatch()
 * @method setActivity
 * @method getActivity
 * @method setNewsfeedGuid
 * @method getThumbnailSrc
 * @method assembleOpts
 * @method dispatch - abstract
 * @author Ben Hayward
 *
 */
abstract class AbstractPermawebDelegate
{
    protected $manager;
    private $activity = null;
    private $newsfeedGuid = '';

    protected function __construct(Manager $manager = null)
    {
        $this->manager = $manager ?: Di::_()->get('Permaweb\Manager');
    }

    /**
     * Sets activity class variable.
     * @param $activity
     * @return AbstractPermawebDelegate - chainable
     */
    public function setActivity($activity): AbstractPermawebDelegate
    {
        $this->activity = $activity;
        return $this;
    }

    /**
     * Gets activity class variable.
     * @return $activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Sets newsfeed ID for use generating minds link.
     * @param string $newsfeedGuid - guid on newsfeed.
     * @return AbstractPermawebDelegate - chainable.
     */
    public function setNewsfeedGuid(string $newsfeedGuid): AbstractPermawebDelegate
    {
        $this->newsfeedGuid = $newsfeedGuid;
        return $this;
    }

    /**
     * Gets thumbnail_src from activity.
     * @return string thumbnail src for an image, or ''.
     */
    private function getThumbnailSrc(): string
    {
        return $this->activity->custom_type === 'batch'
            ? $this->activity->custom_data[0]['src']
            : '';
    }
    
    /**
     * Assembles opts for dispatch.
     * @return array
     */
    public function assembleOpts(): array
    {
        return [
            'text' => $this->activity->getMessage(),
            'guid' => $this->activity->getOwnerGuid(),
            'title' => $this->activity->getTitle() ?: '',
            'thumbnail_src' => $this->getThumbnailSrc() ?: '',
            'minds_link' => $this->manager->getMindsUrl($this->newsfeedGuid),
        ];
    }

    /**
     * abstract - dispatch the call.
     * @return void
     */
    abstract public function dispatch();
}
