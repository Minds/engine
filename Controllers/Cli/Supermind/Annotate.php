<?php

namespace Minds\Controllers\Cli\Supermind;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Interfaces;

class Annotate extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        // TODO
    }

    // Example usage:
    // php cli.php Supermind Annotate annotate --width=1000 --text='supermind request text' --username='myusername' --output='/var/www/Minds/engine/annotatedImage.png'
    public function annotate()
    {
        $width = $this->getOpt('width') ?: 1000;
        $text = $this->getOpt('text') ?: 'supermind request text';
        $username = $this->getOpt('username') ?: 'myusername';
        $output = $this->getOpt('output');

        $manager = Di::_()->get('Media\Imagick\Manager');

        $image = $manager->annotate($width, $text, $username);

        if ($output) {
            header('Content-type: image/png');
            file_put_contents($output, $image->getImageBlob());
        }
        return;
    }
}
