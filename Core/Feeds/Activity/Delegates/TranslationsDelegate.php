<?php
namespace Minds\Core\Feeds\Activity\Delegates;

use Minds\Core\Translation\Storage;
use Minds\Entities\Activity;

class TranslationsDelegate
{
    /** @var Storage */
    private $translationStorage;

    public function __construct($translationStorage = null)
    {
        $this->translationStorage = $translationStorage ?? new Storage();
    }

    /**
     * Clears the translation storage on update
     * @param Activity $activity
     * @return vooid
     */
    public function onUpdate(Activity $activity): void
    {
        $this->translationStorage->purge($activity->guid);
    }
}
