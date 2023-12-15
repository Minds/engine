<?php

namespace Minds\Core\Comments\EmbeddedComments;

use Minds\Core\Comments\EmbeddedComments\Controllers\EmbeddedCommentsGqlController;
use Minds\Core\Comments\EmbeddedComments\Controllers\EmbeddedCommentsPsrController;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsActivityService;
use Minds\Core\Comments\EmbeddedComments\Repositories\EmbeddedCommentsRepository;
use Minds\Core\Comments\EmbeddedComments\Repositories\EmbeddedCommentsSettingsRepository;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsCommentService;
use Minds\Core\Comments\EmbeddedComments\Services\EmbeddedCommentsSettingsService;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\EntitiesBuilder;

class Provider extends DiProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->di->bind(EmbeddedCommentsGqlController::class, function (Di $di): EmbeddedCommentsGqlController {
            return new EmbeddedCommentsGqlController(
                embeddedCommentsActivityService: $di->get(EmbeddedCommentsActivityService::class),
                embeddedCommentsCommentService: $di->get(EmbeddedCommentsCommentService::class),
                embeddedCommentsSettingsService: $di->get(EmbeddedCommentsSettingsService::class),
            );
        });

        $this->di->bind(EmbeddedCommentsPsrController::class, function (Di $di): EmbeddedCommentsPsrController {
            return new EmbeddedCommentsPsrController();
        });

        $this->di->bind(EmbeddedCommentsActivityService::class, function (Di $di): EmbeddedCommentsActivityService {
            return new EmbeddedCommentsActivityService(
                repository: $di->get(EmbeddedCommentsRepository::class),
                embeddedCommentsSettingsService: $di->get(EmbeddedCommentsSettingsService::class),
                config: $di->get(Config::class),
                acl: $di->get('Security\ACL'),
                entitiesBuilder: $di->get(EntitiesBuilder::class),
                metaScraperService: $di->get('Metascraper\Service'),
                activityManager: $di->get('Feeds\Activity\Manager'),
                logger: $di->get('Logger'),
            );
        });

        $this->di->bind(EmbeddedCommentsCommentService::class, function (Di $di): EmbeddedCommentsCommentService {
            return new EmbeddedCommentsCommentService(
                commentsManager: $di->get('Comments\Manager'),
                config: $di->get(Config::class),
                acl: $di->get('Security\ACL'),
                logger: $di->get('Logger'),
            );
        });

        $this->di->bind(EmbeddedCommentsSettingsService::class, function (Di $di): EmbeddedCommentsSettingsService {
            return new EmbeddedCommentsSettingsService(
                repository: $di->get(EmbeddedCommentsSettingsRepository::class),
                cache: $di->get('Cache\PsrWrapper'),
            );
        });

        $this->di->bind(EmbeddedCommentsRepository::class, function (Di $di): EmbeddedCommentsRepository {
            return new EmbeddedCommentsRepository(
                $di->get(Config::class),
                $di->get(Client::class),
                $di->get('Logger')
            );
        });

        $this->di->bind(EmbeddedCommentsSettingsRepository::class, function (Di $di): EmbeddedCommentsSettingsRepository {
            return new EmbeddedCommentsSettingsRepository(
                $di->get(Config::class),
                $di->get(Client::class),
                $di->get('Logger')
            );
        });
    }
}
