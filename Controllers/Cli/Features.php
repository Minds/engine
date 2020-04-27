<?php
/**
 * Features
 *
 * @author edgebal
 */

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager;
use Minds\Exceptions\CliException;
use Minds\Interfaces;

class Features extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /**
     * @inheritDoc
     */
    public function help($command = null)
    {
        $this->out('Syntax usage: cli features sync');
    }

    /**
     * @inheritDoc
     */
    public function exec()
    {
        return $this->help();
    }

    public function sync()
    {
        /** @var Manager $manager */
        $manager = Di::_()->get('Features\Manager');

        $ttl = $this->getOpt('ttl') ?: 300;
        $environmentList = array_filter(explode(',', $this->getOpt('environment') ?: ''));

        if (!$environmentList) {
            throw new CliException('Specify an environment');
        }

        while (true /* Forever running task */) {
            foreach ($environmentList as $environment) {
                $this->out([
                    date('c'),
                    "TTL: {$ttl}",
                    "Environment: {$environment}"
                ], static::OUTPUT_PRE);


                $sync = $manager
                    ->setEnvironment($environment)
                    ->sync($ttl);

                foreach ($sync as $key => $output) {
                    $this->out(sprintf("Sync %s: %s", $key, $output));
                }
            }

            if (!$this->getOpt('forever')) {
                break;
            }

            $this->out("Done, sleeping {$ttl}s");
            sleep($ttl);
        }
    }
}
