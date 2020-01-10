<?php

namespace Minds\Core\SEO\Sitemaps\Modules;

use Minds\Core\Di\Di;
use Minds\Core\Entities;
use Minds\Core\SEO\Sitemaps\SitemapModule;
use Minds\Core\Feeds\Elastic\Manager;

class SitemapTrending extends SitemapModule
{
    /** @var Manager */
    protected $elasticManager;

    public function __construct()
    {
        $this->elasticManager = Di::_()->get('Feeds\Elastic\Manager');
    }

    public function collect($pages, $segments)
    {
        if (isset($pages[0])) {
            $param = $pages[0];
        } else {
            $param = $segments[0];
        }

        $period = $pages[1] ?? '24h';

        switch ($param) {
            case 'activity':
              $key = 'activity';
              break;
            case 'channels':
                $key = 'user';
                break;
            case 'images':
                $key = 'object:image';
                break;
            case 'videos':
                $key = 'object:video';
                break;
            case 'blogs':
                $key = 'object:blog';
                break;
            case 'groups':
                $key = 'group';
                break;
        }

        $entities = $this->getEntities($key, $period);
        foreach ($entities as $entity) {
            $route = '';
            switch ($param) {
                case 'activity':
                    $route = 'newsfeed/' . $entity->guid;
                    break;
                case 'images':
                case 'videos':
                    $route = 'media/' . $entity->guid;
                    break;
                case 'channels':
                    $route = $entity->username;
                    break;
                case 'blogs':
                    $route = $entity->getUrl(true);
                    break;
                case 'groups':
                    $route = 'groups/profile' . $entity->guid;
                    break;
            }

            $this->addOrUpdateRoute($route, $entity->time_updated ?: $entity->time_created);
        }
    }

    private function getEntities($key, $period = '24h')
    {
        if (!isset($key)) {
            return [];
        }

        $result = $this->elasticManager->getList([
            'type' => $key,
            'limit' => 500,
            'sync' => false,
            'algorithm' => 'top',
            'period' => $period,
        ]);
        
        return $result;
    }
}
