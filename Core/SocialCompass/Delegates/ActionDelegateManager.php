<?php

namespace Minds\Core\SocialCompass\Delegates;

use Minds\Core\Di\Di;

/**
 * Handles all action delegates for the social compass -
 * delegates that act upon scores set by the user as their social compass answers.
 */
class ActionDelegateManager
{
    // Array of all delegate classes.
    private const DELEGATES = [
        OpenBoostDelegate::class
    ];

    /**
     * Calls all action delegates iteratively.
     * @param array $answers - array of AnswerModel objects a user has provided.
     * @return void
     */
    public function onAnswersProvided(array $answers): void
    {
        foreach (self::DELEGATES as $delegate) {
            $delegate = new $delegate();
            $delegate->onAnswersProvided($answers);
        }
    }
}
