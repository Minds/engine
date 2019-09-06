<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Pro;

use Exception;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\SEO\Manager;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Helpers;

class SEO
{
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Config */
    protected $config;

    /** @var User */
    protected $user;

    public function __construct(EntitiesBuilder $entitiesBuilder = null, Config $config = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param User $user
     * @return SEO
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param Settings $proSettings
     * @throws Exception
     */
    public function setup(Settings $proSettings)
    {
        Manager::reset();

        $title = $proSettings->getTitle() ?: $this->user->name;

        $tagList = array_map(function ($tag) {
            return $tag['tag'];
        }, $proSettings->getTagList());

        Manager::setDefaults([
            'title' => $title,
            'description' => $proSettings->getOneLineHeadline(),
            'keywords' => implode(',', $tagList),
            'og:title' => $title,
            'og:url' => $proSettings->getDomain(),
            'og:description' => $proSettings->getOneLineHeadline(),
            'og:type' => 'website',
            'og:image' => $this->user->getIconURL('large'),
        ]);

        Manager::add('/feed', [$this, 'activityHandler']);
        Manager::add('/videos', [$this, 'entityHandler']);
        Manager::add('/images', [$this, 'entityHandler']);
        Manager::add('/articles', [$this, 'entityHandler']);
        Manager::add('/groups', [$this, 'entityHandler']);
//        Manager::add('/login', [$this, 'entityHandler']);

        // blog slugs

        // media slugs
    }

    function activityHandler($slugs = [])
    {
        if (isset($slugs[0]) && is_numeric($slugs[0])) {
            $activity = new Activity($slugs[0]);

            if (!$activity->guid || Helpers\Flags::shouldFail($activity)) {
                header("HTTP/1.0 404 Not Found");
                return [
                    'robots' => 'noindex',
                ];
            }
            if ($activity->paywall) {
                return;
            }

            $title = $activity->title ?: $activity->message;
            $description = $activity->blurb ?: "@{$activity->ownerObj['username']} on {$this->config->site_name}";

            $meta = [
                'title' => $title,
                'description' => $description,
                'og:title' => $title,
                'og:description' => $description,
                'og:url' => $activity->getUrl(),
                'og:image' => $activity->custom_type == 'batch' ? $activity->custom_data[0]['src'] : $activity->thumbnail_src,
                'og:image:width' => 2000,
                'og:image:height' => 1000,
                'twitter:site' => '@minds',
                'twitter:card' => 'summary',
                'al:ios:url' => 'minds://activity/' . $activity->guid,
                'al:android:url' => 'minds://minds/activity/' . $activity->guid,
                'robots' => 'all',
            ];

            if ($activity->custom_type == 'video') {
                $meta['og:type'] = "video";
                $meta['og:image'] = $activity->custom_data['thumbnail_src'];
            }

            return $meta;
        }
    }

    function getEntityProperty($entity, $prop)
    {
        $getter = "get${$prop}";
        return Helpers\MagicAttributes::getterExists($entity, $getter) ? $entity->{$getter}() : $entity->{$prop};
    }

    function entityHandler($slugs = [])
    {
        if (isset($slugs[0]) && is_numeric($slugs[0])) {
            $entity = $this->entitiesBuilder->single($slugs[0]);

            if (!$entity->guid || Helpers\Flags::shouldFail($entity)) {
                header("HTTP/1.0 404 Not Found");
                return [
                    'robots' => 'noindex',
                ];
            }
            if ($entity->paywall) {
                return;
            }

            $owner = $this->getEntityProperty($entity, 'ownerObj');

            $title = $this->getEntityProperty($entity, 'title') ?: $this->getEntityProperty($entity, 'message');

            $siteName = $this->config->site_name;

            $description = $this->getEntityProperty($entity, 'blurb') ?: "@{$owner['username']} on {$siteName}";

            $meta = [
                'title' => $title,
                'description' => $description,
                'og:title' => $title,
                'og:description' => $description,
                'og:url' => $entity->getUrl(),
                'og:image:width' => 2000,
                'og:image:height' => 1000,
                'robots' => 'all',
            ];

            switch ($entity->subtype) {
                case 'video':
                    $meta['og:type'] = "video";
                    $meta['og:image'] = $entity->getIconUrl();
                    break;
                case 'image':
                    $meta['og:type'] = "image";
                    $meta['og:image'] = $entity->getIconUrl();
                    break;
                case 'blog':
                    $meta['og:type'] = "blog";
                    $meta['og:image'] = $entity->getIconUrl();
                    break;
                case 'group':
                    $meta['og:type'] = "group";
                    $meta['og:image'] = $this->config->cdn_url . 'fs/v1/banner/' . $entity->banner;
                    break;
            }

            return $meta;
        }
    }
}
