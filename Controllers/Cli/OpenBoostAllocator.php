<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomQuery;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;

/**
 * OpenBoostAllocator
 * Allocates open boost preference to "Open" if response to social compass quiz meets specified criteria.
 */
class OpenBoostAllocator extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(
        ?Scroll $scroll = null,
        ?EntitiesBuilder $entitiesBuilder = null,
        ?Save $saveAction = null
    ) {
        $this->scroll = $scroll ?? Di::_()->get('Database\Cassandra\Cql\Scroll');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->saveAction = $saveAction ?? new Save();
    }

    /**
     * Simple help function.
     * @return void
     */
    public function help($command = null): void
    {
        $this->out('Syntax usage: cli.php OpenBoostAllocator');
    }

    /**
     * Main function, applies boost rating of open to users who meet the criteria
     * from their answers to the social compass quiz
     * @return void
     */
    public function exec(): void
    {
        // get ALL rows.
        $statement = "SELECT * FROM social_compass_answers";
        $prepared = new CustomQuery();
        $prepared->query($statement);

        // initializing an array to be of format `user_guid` => `count of answers above threshold`.
        $users = [];

        // iterate through rows.
        foreach ($this->scroll->request($prepared) as $row) {
            $userGuid = $row['user_guid']->value();

            // if threshold is met for a given threshold id
            if ($row['current_value'] > 69 && in_array($row['question_id'], [
                'ChallengingOpinionsQuestion',
                'DebatedMisinformationQuestion',
                'MatureContentQuestion'
             ], true)) {
                // init count to 0
                if (!isset($users[$userGuid])) {
                    $users[$userGuid] = 0;
                }
                // increment count
                ++$users[$userGuid];
            }
        }

        // iterate through all users stored from previous loop
        foreach ($users as $userGuid => $userCounts) {
            // if count is 3, they have answered 3 questions above the score threshold.
            if ($userCounts >= 3) {
                $user = $this->entitiesBuilder->single($userGuid);
                $user->setBoostRating(2); // enabled.
                $this->saveAction->setEntity($user)->save();
            }
        }
    }
}
