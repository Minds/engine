<?php
/**
 * Neo4j data warehouse
 */
 
namespace Minds\Core\Data\Warehouse;

use Minds\Core;
use Minds\Core\Data\Interfaces;
use Minds\Core\Data\Neo4j\Prepared;
use Minds\Traits\Logger;

class Neo4j implements Interfaces\WarehouseJobInterface
{
    use Logger;

    private $client;
 
    /**
     * Run the job
     * @return void
     */
    public function run(array $slugs = array())
    {
        $this->client = \Minds\Core\Data\Client::build('Neo4j');
        switch ($slugs[0]) {
            case 'sync':
                array_shift($slugs);
                $this->sync($slugs);
                break;
        }
    }
    
    /**
     * Syncronise the data
     */
    public function sync($slugs = array())
    {
        switch ($slugs[0]) {
            case 'users':
                $this->syncUsers();
            break;
            case 'subscriptions':
                $this->logger()->warning('Sorry, please transfer users first..');
            break;
            case 'videos':
                $this->syncVideos();
            break;
            case 'images':
                $this->syncImages();
                break;
            default:
                $this->syncUsers();
                $this->syncVideos();
        }
    
        
        
        return $this;
    }

    /**
     * Sync users, with their subscriptions
     */
    public function syncUsers()
    {
        $subscriptions = new \Minds\Core\Data\Call('friends');
        $prepared = new Prepared\Common();
        $attempts = 0;
        $offset = '100000000000002247';
        while (true) {
            $this->logger()->info("Syncing 50 users from $offset");
            $users = core\Entities::get(array('type'=>'user', 'offset'=>$offset, 'limit'=>50));
            if (!is_array($users) || end($users)->guid == $offset) {
                break;
            }
            $last_offset = $offset;
            $offset = end($users)->guid;
            try {
                $this->client->request($prepared->createBulkUsers($users));
                $this->logger()->info("Imported users");
            } catch (\Exception $e) {
                if ($attempts == 0) {
                    $this->logger()->error("Hmm.. slight issue, re-running ({$e->getMessage()})");
                    $offset = $last_offset;
                    $attempts++;
                    continue;
                } else {
                    $this->logger()->error("Hmm.. slight issue, skipping ({$e->getMessage()})");
                }
            }
            $guids = array();
            foreach ($users as $user) {
                $guids[] = $user->guid;
            }
            try {
                $bulk_subscriptions = $subscriptions->getRows($guids);
                foreach ($bulk_subscriptions as $subscriber => $us) {
                    $us = array_splice($us, 0, 200);
                    $this->client->request($prepared->createBulkSubscriptions(array($subscriber=>$us)));
                    $this->logger()->info("Imported subscriptions");
                }
            } catch (\Exception $e) {
                $this->logger()->error("Hmm.. slight issue, re-running ({$e->getMessage()})");
            }
           // break;
           // exit;
        }
    }

    /**
     * sync videos
     * @return void
     */
    public function syncVideos()
    {
        $prepared = new Prepared\Common();
        //transfer over videos
        $offset = "";
        while (true) {
            $this->logger()->info("Syncing 250 videos from $offset");
            $videos = core\Entities::get(array('subtype'=>'video', 'offset'=>$offset, 'limit'=>250));
            if (!is_array($videos) || end($videos)->guid == $offset) {
                break;
            }
            $offset = end($videos)->guid;
            $this->client->request($prepared->createBulkObjects($videos, 'video'));
            $this->logger()->info("Imported videos");
        }
    }

    /**
     * sync images
     */
    public function syncImages()
    {
        $prepared = new Prepared\Common();
        $offset = "";
        while (true) {
            $this->logger()->info("Syncing 250 images from $offset");
            $images = core\Entities::get(array('subtype'=>'image', 'offset'=>$offset, 'limit'=>250));
            if (!is_array($images) || end($images)->guid == $offset) {
                break;
            }
            $offset = end($images)->guid;
            $this->client->request($prepared->createBulkObjects($images, 'image'));
            $this->logger()->info("Imported images");
        }
    }

    /**
     * sync users
     * @param array (optional) $users
     * @return void
     */
    public function syncThumbs($users = null)
    {
        $indexes = new Core\Data\Call('entities_by_time');
        if (!$users && !is_array($users)) {
            while (true) {
            }
        }
    
        foreach ($users as $user) {
            $thumbs_up = array();
        }
    }
}
