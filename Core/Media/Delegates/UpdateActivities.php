<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Media\Delegates;

use Minds\Core\Data\Call;
use Minds\Entities\Image;
use Minds\Entities\Video;

class UpdateActivities
{
    /** @var Call */
    private $indexDb;
    private $entityDb;


    public function __construct($indexDb = null, $entityDb = null)
    {
        $this->indexDb = $indexDb ?: new Call('entities_by_time');
        $this->entityDb = $entityDb ?: new Call('entities');
    }

    /**
     * @param Image|Video $entity
     */
    public function updateActivities($entity)
    {
        foreach ($this->indexDb->getRow("activity:entitylink:{$entity->guid}") as $guid => $ts) {
            $this->entityDb->insert($guid, ['message' => $entity->title]);

            $parameters = $entity->getActivityParameters();
            $this->entityDb->insert($guid, ['custom_type' => $parameters[0]]);
            $this->entityDb->insert($guid, ['custom_data' => json_encode($parameters[1])]);
        }
    }
}
