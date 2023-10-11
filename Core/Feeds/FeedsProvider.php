<?php

namespace Minds\Core\Feeds;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider;
use Minds\Core\Feeds\ClusteredRecommendations\MySQLRepository;
use Minds\Core\Groups\V2\Membership;

class FeedsProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Feeds\Elastic\Manager', function ($di) {
            return new Elastic\Manager();
        });

        $this->di->bind('Feeds\Activity\Manager', function ($di) {
            return new Activity\Manager();
        });

        $this->di->bind('Feeds\Firehose\Manager', function ($di) {
            return new Firehose\Manager();
        });

        $this->di->bind('Feeds\Seen\Manager', function ($di) {
            return new Seen\Manager();
        });

        $this->di->bind('Feeds\ClusteredRecommendations\Manager', function ($di) {
            return new ClusteredRecommendations\Manager();
        });

        $this->di->bind('Feeds\Subscribed\Manager', function ($di) {
            return new Subscribed\Manager();
        });

        //

        $this->di->bind('Feeds\Controller', function ($di) {
            return new Controller();
        });

        $this->di->bind('Feeds\Activity\Controller', function ($di) {
            return new Activity\Controller();
        });

        $this->di->bind('Feeds\Activity\OgImageController', function ($di) {
            return new Activity\OgImagesController();
        });

        $this->di->bind('Feeds\Activity\InteractionCounters', function ($di) {
            return new Activity\InteractionCounters();
        });

        $this->di->bind('Feeds\UnseenTopFeed\Controller', function ($di) {
            return new UnseenTopFeed\Controller();
        });

        $this->di->bind('Feeds\UnseenTopFeed\Manager', function ($di) {
            return new UnseenTopFeed\Manager();
        });

        $this->di->bind('Feeds\ClusteredRecommendations\Controller', function ($di) {
            return new ClusteredRecommendations\Controller();
        });

        $this->di->bind('Feeds\Subscribed\Controller', function ($di) {
            return new Subscribed\Controller();
        });

        $this->di->bind('Feeds\User\Manager', function ($di) {
            return new User\Manager();
        });

        // V2

        $this->di->bind(Elastic\V2\Manager::class, function (Di $di) {
            return new Elastic\V2\Manager(
                $di->get('Database\ElasticSearch'),
                new MySQLRepository(),
                $di->get('Feeds\Seen\Manager'),
                $di->get('EntitiesBuilder'),
                $di->get(Membership\Manager::class),
                $di->get('Security\ACL'),
                $di->get('Experiments\Manager'),
                $di->get(Config::class),
            );
        });
    }
}
