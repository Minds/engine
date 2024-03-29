<?php

/**
 * Minds Blogs Create Activity Delegate
 *
 * @author emi
 */

namespace Minds\Core\Blogs\Delegates;

use Minds\Core\Blogs\Blog;
use Minds\Core\Data\Call;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;

class CreateActivity
{
    /** @var Save */
    protected $saveAction;

    /** @var Call */
    protected $db;

    /**
     * CreateActivity constructor.
     * @param null $saveAction
     */
    public function __construct($saveAction = null, Call $db=null, protected ?EntitiesBuilder $entitiesBuilder = null)
    {
        $this->saveAction = $saveAction ?: new Save();
        $this->db = $db ?? new Call('entities_by_time');
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
    }

    /**
     * Creates a new activity for a blog
     * @param Blog $blog
     * @throws \Minds\Exceptions\StopEventException
     * @return bool
     */
    public function save(Blog $blog) : bool
    {
        $activities = $this->db->getRow("activity:entitylink:{$blog->getGuid()}");
        if (!empty($activities)) {
            foreach ($activities as $guid) {
                $activity = new Activity($guid);
                $activity->setTimeCreated($blog->getTimeCreated());
                $this->saveAction
                ->setEntity($activity)
                ->save();
            }
            return true;
        }

        /** @var User */
        $owner = $this->entitiesBuilder->single($blog->getOwnerGuid());

        $activity = (new Activity())
            ->setLinkTitle($blog->getTitle())
            ->setBlurb(strip_tags($blog->getBody()))
            ->setURL($blog->getURL())
            ->setThumbnail($blog->getIconUrl())
            ->setFromEntity($blog)
            ->setMature($blog->isMature())
            ->setNsfw($blog->getNsfw())
            ->setOwner($owner->export())
            ->setWireThreshold($blog->getWireThreshold())
            ->setPaywall($blog->isPaywall())
            ->setAccessId($blog->getAccessId());

        $activity->container_guid = $owner->guid;
        $activity->owner_guid = $owner->guid;
        $activity->ownerObj = $owner->export();
        $activity->setTimeCreated($blog->getTimeCreated());

        $this->saveAction
            ->setEntity($activity)
            ->save();

        return true;
    }
}
