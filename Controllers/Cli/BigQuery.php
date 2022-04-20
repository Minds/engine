<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Blockchain\BigQuery\HoldersQuery;
use Minds\Interfaces;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\Manager as UniqueOnChainManager;

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
     *
     * # Usage Example
     * ## Count time and rows for ALL addresses found
     * php cli.php BigQuery getHolders
     *
     * ## Count time and rows for all addresses linked to a registered account.
     * php cli.php BigQuery getHolders --onlyRegistered=true
     *
     * ## Count time and rows for all addresses linked to a registered account.
     * ## Use verbose flag which will dump the holders object.
     * php cli.php BigQuery getHolders --onlyRegistered=true --verbose=true
     *
     * @return void
     */
    public function getHolders(): void
    {
        $onlyRegistered = $this->getOpt('onlyRegistered');
        $verbose = $this->getOpt('verbose');

        $uniqueOnChainManager = new UniqueOnChainManager();

        if ($onlyRegistered) {
            $this->out("Getting all holders with balances who are on-site...");
        } else {
            $this->out("Getting all holders with balances...");
        }

        $start = hrtime(true);

        $directHoldersQuery = new HoldersQuery();

        $result = $onlyRegistered ?
            $uniqueOnChainManager->getAll() :
            $directHoldersQuery->get();

        $count = 0;
        foreach ($result as $holder) {
            if ($verbose) {
                $this->out("Row $count: " . var_export($holder, true));
            }
            $count++;
        }

        $timeTaken = (hrtime(true) - $start) / 1e+6;
        $this->out("The operation took $timeTaken ms...");
        $this->out("Iterated over $count rows.");
    }
}
