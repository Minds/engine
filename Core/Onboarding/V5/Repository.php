<?php
declare(strict_types=1);

namespace Minds\Core\Onboarding\V5;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Onboarding\V5\GraphQL\Types\OnboardingState;
use Minds\Core\Onboarding\V5\GraphQL\Types\OnboardingStepProgressState;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

/**
 * Onboarding V5 repository.
 */
class Repository extends AbstractRepository
{
    /**
     * Gets onboarding state for a given user guid.
     * @param int $userGuid - user guid to get state for.
     * @return ?OnboardingState
     */
    public function getOnboardingState(int $userGuid): ?OnboardingState
    {
        $statement = $this->mysqlClientReaderHandler
            ->select()
            ->from('minds_onboarding_completion')
            ->where('user_guid', Operator::EQ, new RawExp(':user_guid'))
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'user_guid' => $userGuid,
        ]);

        try {
            $statement->execute();

            if ($statement->rowCount() < 1) {
                return null;
            }

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return new OnboardingState(
                $userGuid,
                $row['started_at'] ? strtotime($row['started_at']) : 1,
                $row['completed_at'] ? strtotime($row['completed_at']) : null
            );
        } catch (PDOException $e) {
            $this->logger->error("Query error details: ", $statement->errorInfo());
            throw $e;
        }
    }

    /**
     * Sets onboarding state for a given user guid.
     * @param int $userGuid - user guid to set state for.
     * @param bool $isCompleted - whether to mark onboarding state as completed.
     * @return OnboardingState current onboarding state.
     */
    public function setOnboardingState(int $userGuid, bool $isCompleted = false): OnboardingState
    {
        $statement = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_onboarding_completion')
            ->set([
                'user_guid' => new RawExp(':user_guid'),
                'completed_at' => new RawExp(':completed_at')
            ])
            ->onDuplicateKeyUpdate([
                'completed_at' => new RawExp(':completed_at')
            ])
            ->prepare();

        $completedAtDateString = date('Y-m-d H:i:s');

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'user_guid' => $userGuid,
            'completed_at' => $isCompleted ? $completedAtDateString : null
        ]);

        try {
            $statement->execute();

            return new OnboardingState(
                $userGuid,
                1,
                $isCompleted ? strtotime($completedAtDateString) : null
            );
        } catch (PDOException $e) {
            $this->logger->error("Query error details: ", $statement->errorInfo());
            throw $e;
        }
    }

    /**
     * Gets onboarding step progress state for a given user guid.
     * @param int $userGuid - user guid to get step progress state for.
     * @return iterable
     */
    public function getOnboardingStepProgress(int $userGuid): iterable
    {
        $statement = $this->mysqlClientReaderHandler
            ->select()
            ->from('minds_onboarding_step_progress')
            ->where('user_guid', Operator::EQ, new RawExp(':user_guid'))
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'user_guid' => $userGuid,
        ]);

        try {
            $statement->execute();
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
                yield new OnboardingStepProgressState(
                    userGuid: (int) $row['user_guid'],
                    stepKey: $row['step_key'],
                    stepType: $row['step_type'],
                    completedAt: strtotime($row['completed_at'])
                );
            }
        } catch (PDOException $e) {
            $this->logger->error("Query error details: ", $statement->errorInfo());
            throw $e;
        }
    }

    /**
     * Sets onboarding step to completed for a given user guid.
     * @param int $userGuid - user guid to set the completed state for.
     * @param string $stepKey - step key of step.
     * @param string $stepType - step type of step.
     * @return OnboardingStepProgressState current step progress state.
     */
    public function completeOnboardingStep(
        int $userGuid,
        string $stepKey,
        string $stepType,
    ): OnboardingStepProgressState {
        $completedAtTimestamp = time();
        $completedAtDateString = date('c', $completedAtTimestamp);

        $statement = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_onboarding_step_progress')
            ->set([
                'user_guid' => new RawExp(':user_guid'),
                'step_key' => new RawExp(':step_key'),
                'step_type' => new RawExp(':step_type'),
                'completed_at' => new RawExp(':completed_at')
            ])
            ->onDuplicateKeyUpdate([
                'completed_at' => new RawExp(':completed_at')
            ])
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'user_guid' => $userGuid,
            'step_key' => $stepKey,
            'step_type' => $stepType,
            'completed_at' => $completedAtDateString
        ]);

        try {
            $statement->execute();
            return new OnboardingStepProgressState(
                userGuid: $userGuid,
                stepKey: $stepKey,
                stepType: $stepType,
                completedAt: $completedAtTimestamp
            );
        } catch (PDOException $e) {
            $this->logger->error("Query error details: ", $statement->errorInfo());
            throw $e;
        }
    }
}
