<?php
/**
 * Album entity
 *
 * Albums are containers for other entities and also act as PAM controllers
 */
namespace Minds\Entities;

use Minds\Api;
use Minds\Core;
use Minds\Core\Data;

class Album extends MindsObject
{
    protected function initializeAttributes()
    {
        parent::initializeAttributes();

        $this->attributes['super_subtype'] = 'archive';
        $this->attributes['subtype'] = "album";
        $this->attributes['access_id'] = 2;
    }

    public function getURL()
    {
        return elgg_get_site_url() . 'media/'.$this->guid;
    }

    /**
     * Get the icon url. This is configurable to be multiple images from the album or
     * just a specific image. It defaults the the latest image in the album
     */
    public function getIconURL($size = 'large')
    {
        global $CONFIG; //@todo remove globals!
        return $CONFIG->cdn_url . 'fs/v1/thumbnail/' . $this->guid . '/'.$size;
    }

    public function getChildrenGuids($limit = 1000000, $offset = '')
    {
        $index = new Data\indexes('object:container');
        if ($guids = $index->get($this->guid, ['limit'=>$limit, 'offset'=>$offset])) {
            $return = [];
            foreach ($guids as $guid => $ts) {
                $return[] = (string) $guid;
            }
            return $return;
        }
        return false;
    }

    public function getChildren($limit = 12)
    {
        $guids = $this->getChildrenGuids($limit);
        if (!$guids) {
            return [];
        }
        $entities = Core\Entities::get(['guids'=>$guids]);
        return $entities;
    }

    public function addChildren($guids)
    {
        $rows = [];
        foreach ($guids as $guid => $ts) {
            if (!$guid) {
                continue;
            }
            $rows[$guid] = ['container_guid' => $this->guid, 'access_id' => $this->access_id];
        }

        if ($rows) {
            $db = new Data\Call('entities');
            $db->insertBatch($rows);
        }

        $db = new Data\Call('entities_by_time');
        $db->insert("object:container:$this->guid", $guids);
    }

    /**
     * Extend the default entity save function to update the remote service
     *
     */
    public function save($public = true)
    {
        $this->super_subtype = 'archive';
        //$this->access_id = 2;
        parent::save($public);
        return $this->guid;
    }

    /**
     * Extend the default delete function to remove from the remote service
     */
    public function delete()
    {
        return parent::delete();
        //delete all children too.
    }

    public function getFilePath()
    {
    }

    public function getExportableValues()
    {
        return array_merge(parent::getExportableValues(), [
            'thumbnail',
            'images',
            'license'
        ]);
    }

    public function export()
    {
        $export = parent::export();
        $export['images'] = $this->getChildrenGuids(5);
        $export['description'] = $this->description; //videos need to be able to export html.. sanitize soon!
        return $export;
    }
}
