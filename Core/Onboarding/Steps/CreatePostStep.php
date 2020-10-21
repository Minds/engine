<?php
/**
 * CreatePostStep
 *
 * @author Mark Harding
 */

namespace Minds\Core\Onboarding\Steps;

use Minds\Entities\User;
use Minds\Core\Feeds\Elastic;
use Minds\Core\Di\Di;

class CreatePostStep implements OnboardingStepInterface
{
    /** @var Elastic\Manager */
    protected $elasticFeedManager;

    public function __construct($elasticFeedManager = null)
    {
        $this->elasticFeedManager = $elasticFeedManager ?? Di::_()->get('Feeds\Elastic\Manager');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isCompleted(User $user)
    {
        return count($this->elasticFeedManager->getList([
            'type' => 'activity',
            'owner_guid' => $user->getGuid(),
            'algorithm' => 'latest',
            'period' => 'relevant',
            'limit' => 1,
        ])) === 1;
    }
}
