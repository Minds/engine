<?php

namespace Minds\Core\SocialCompass\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;

/**
 * Auto-enrols users into seeing Open Boosts given the control questions
 * meet the defined thresholds.
 */
class OpenBoostDelegate extends AbstractActionDelegate
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Save $saveAction = null
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->saveAction = $saveAction ?? new Save();

        $this->controlQuestions = [
            'ChallengingOpinionsQuestion',
            'DebatedMisinformationQuestion',
            'MatureContentQuestion'
        ];
    }

    /**
     * Handles passed in scores and if threshold is met, sets boost rating to 2.
     * @param array $scores - QuestionName => Score array of user's answers for control questions.
     * @param string $userGuid - users guid.
     * @return void
     */
    protected function handleScores(array $scores, string $userGuid): void
    {
        if (
            $scores['ChallengingOpinionsQuestion'] > 69 &&
            $scores['DebatedMisinformationQuestion'] > 69 &&
            $scores['MatureContentQuestion'] > 69
        ) {
            $user = $this->entitiesBuilder->single($userGuid);
            $user->setBoostRating(2); // enabled.
            $this->saveAction->setEntity($user)->save();
        }
    }
}
