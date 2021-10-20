<?php

namespace Minds\Core\SocialCompass\Questions\Manifests;

use Minds\Core\SocialCompass\Questions\EstablishmentQuestion;
use Minds\Core\SocialCompass\Questions\OpinionsDisagreementQuestion;
use Minds\Core\SocialCompass\Questions\PoliticalBeliefsQuestion;
use Minds\Core\SocialCompass\Questions\PoliticalContentQuestion;

class QuestionsManifestV1 implements QuestionsManifestInterface
{
    public const Questions = [
        PoliticalContentQuestion::class,
        OpinionsDisagreementQuestion::class,
        PoliticalBeliefsQuestion::class,
        EstablishmentQuestion::class,
    ];
}
