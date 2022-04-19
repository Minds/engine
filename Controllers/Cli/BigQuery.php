<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Blockchain\BigQuery\HoldersQuery;
use Minds\Interfaces;

/**
 * CLI for manually running BigQuery queries.
 */
class BigQuery extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /**
     * Echoes $commands (or overall) help text to standard output.
     * @param  ?string $command - the command to be executed. If null, it corresponds to exec()
     * @return void
     */
    public function help($command = null): void
    {
        $this->out('Usage: cli BigQuery');
    }

    /**
     * Executes the default command.
     * @return void
     */
    public function exec(): void
    {
        $this->help();
    }

    /**
     * Gets information on rows and time taken without dumping query results.
     * @return void
     */
    public function getHoldersWithBalances(): void
    {
        error_log("Getting all holders with balances...");
        $start = hrtime(true);

        $manager = new HoldersQuery();
        $result = $manager->get();

        $count = 0;
        foreach ($result as $holder) {
            $count++;
        }

        $timeTaken = (hrtime(true) - $start) / 1e+6;
        error_log("The operation took $timeTaken ms...");
        error_log("Iterated over $count rows.");
    }
}
