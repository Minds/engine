<?php

namespace Minds\Core\Media\YouTubeImporter;

use Minds\Core\Di\Provider as DiProvider;

class Provider extends DiProvider
{
    public function register()
    {
        $this->di->bind('Media\YouTubeImporter\Controller', function ($di) {
            return new Controller();
        });

        $this->di->bind('Media\YouTubeImporter\Repository', function ($di) {
            return new Repository();
        });

        $this->di->bind('Media\YouTubeImporter\Manager', function ($di) {
            return new Manager();
        });

        $this->di->bind('Media\YouTubeImporter\YTClient', function ($di) {
            return new YTClient();
        }, [ 'useFactory' => true ]);
    }
}
