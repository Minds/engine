<?php

namespace Minds\Core\SocialCompass\Questions\Manifests;

use Minds\Core\SocialCompass\Questions\EstablishmentQuestion;
use Minds\Core\SocialCompass\Questions\OpinionsDisagreementQuestion;
use Minds\Core\SocialCompass\Questions\PoliticalBeliefsQuestion;
use Minds\Core\SocialCompass\Questions\PoliticalContentQuestion;

/**
 * The manifest defining the list of questions available to the user
 * in the Social Compass module
 */
class QuestionsManifestV1 extends QuestionsManifest
{
    public const QUESTIONS = [
        PoliticalContentQuestion::class,
        OpinionsDisagreementQuestion::class,
        PoliticalBeliefsQuestion::class,
        EstablishmentQuestion::class,
    ];
}
