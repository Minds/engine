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

    /**
     * SEO constructor.
     * @param EntitiesBuilder $entitiesBuilder
     * @param Config $config
     */
    public function __construct(EntitiesBuilder $entitiesBuilder = null, Config $config = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param User $user
     * @return SEO
     */
    public function setUser(User $user): SEO
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param Settings $proSettings
     * @throws Exception
     */
    public function setup(Settings $proSettings): void
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

        Manager::add('/', function () {
        });

        Manager::add('/newsfeed', [$this, 'activityHandler']);
        Manager::add('/media', [$this, 'entityHandler']);
        // blog route added in Blogs\SEO
    }

    /**
     * @param array $slugs
     * @return array|null
     */
    public function activityHandler($slugs = []): ?array
    {
        if (!isset($slugs[0]) || !is_numeric($slugs[0])) {
            return null;
        }

        $activity = new Activity($slugs[0]);

        if (!$activity->guid || Helpers\Flags::shouldFail($activity)) {
            header("HTTP/1.0 404 Not Found");
            return [
                'robots' => 'noindex',
            ];
        }
        if ($activity->paywall) {
            return null;
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

    /**
     * @param $entity
     * @param $prop
     * @return mixed
     */
    public function getEntityProperty($entity, $prop)
    {
        $getter = "get${$prop}";

        if (isset($entity->{$prop})) {
            return $entity->{$prop};
        } elseif (Helpers\MagicAttributes::getterExists($entity, $getter)) {
            return $entity->{$getter}();
        }

        return null;
    }

    /**
     * @param array $slugs
     * @return array|null
     */
    public function entityHandler($slugs = []): ?array
    {
        if (!isset($slugs[0]) || !is_numeric($slugs[0])) {
            return null;
        }

        $entity = $this->entitiesBuilder->single($slugs[0]);

        if (!$entity->guid || Helpers\Flags::shouldFail($entity)) {
            header("HTTP/1.0 404 Not Found");

            return [
                'robots' => 'noindex',
            ];
        }
        if ($entity->paywall) {
            return null;
        }

        $owner = $this->getEntityProperty($entity, 'ownerObj');

        $title = $this->getEntityProperty($entity, 'title') ?: $this->getEntityProperty($entity, 'description');

        $siteName = $this->config->site_name;

        $description = $title ?? $this->getEntityProperty($entity, 'blurb') ?? "@{$owner['username']} on {$siteName}";

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
