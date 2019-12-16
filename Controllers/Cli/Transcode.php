<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Entities;

class Transcode extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $entity = Di::_()->get('EntitiesBuilder')->single($this->getOpt('guid'));

        if (!$entity) {
            $this->out('Entity not found');
            return;
        }

        $manager = Di::_()->get('Media\Video\Transcoder\Manager');
        $manager->createTranscodes($entity);
    }

    /**
     * Retries the transcode on the current thread
     * @return void
     */
    public function retry()
    {
        $entity = Di::_()->get('EntitiesBuilder')->single($this->getOpt('guid'));

        if (!$entity) {
            $this->out('Entity not found');
            return;
        }

        $manager = Di::_()->get('Media\Video\Transcoder\Manager');
        $transcode = $manager->getList([
            'guid' => $this->getOpt('guid'),
            'profileId' => $this->getOpt('profile-id'),
        ])[0];

        if (!$transcode) {
            $this->out('Transcode not found');
            return;
        }

        $manager->transcode($transcode);
    }
}
