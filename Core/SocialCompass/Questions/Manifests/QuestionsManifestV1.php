<?php

namespace Minds\Core\SocialCompass\Questions\Manifests;

use Minds\Core\SocialCompass\Questions\AnyoneChangeTheirMindQuestion;
use Minds\Core\SocialCompass\Questions\ApoliticalContentQuestion;
use Minds\Core\SocialCompass\Questions\BannedMisinformationQuestion;
use Minds\Core\SocialCompass\Questions\ChallengingOpinionsQuestion;
use Minds\Core\SocialCompass\Questions\DebatedMisinformationQuestion;
use Minds\Core\SocialCompass\Questions\ImprovingWorldQuestion;
use Minds\Core\SocialCompass\Questions\MatureContentQuestion;
use Minds\Core\SocialCompass\Questions\NeverCompromisedPersonalPrivacyQuestion;
use Minds\Core\SocialCompass\Questions\PersonalFreedomQuestion;
use Minds\Core\SocialCompass\Questions\PhonesAddictedPeopleQuestion;
use Minds\Core\SocialCompass\Questions\PoliticalContentQuestion;
use Minds\Core\SocialCompass\Questions\RequiredGovernmentRegulationsQuestion;
use Minds\Core\SocialCompass\Questions\SameOpinionsQuestion;
use Minds\Core\SocialCompass\Questions\SeeMemesQuestion;

/**
 * The manifest defining the list of questions available to the user
 * in the Social Compass module
 */
class QuestionsManifestV1 extends QuestionsManifest
{
    public const QUESTIONS = [
        ChallengingOpinionsQuestion::class,
        SameOpinionsQuestion::class,
        BannedMisinformationQuestion::class,
        DebatedMisinformationQuestion::class,
        RequiredGovernmentRegulationsQuestion::class,
        PersonalFreedomQuestion::class,
        PoliticalContentQuestion::class,
        ApoliticalContentQuestion::class,
        SeeMemesQuestion::class,
        NeverCompromisedPersonalPrivacyQuestion::class,
        AnyoneChangeTheirMindQuestion::class,
        PhonesAddictedPeopleQuestion::class,
        ImprovingWorldQuestion::class,
        MatureContentQuestion::class,
    ];
}
