<?php
namespace Minds\Core\Onboarding;

use Minds\Entities\User;
use Exception;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Onboarding Controller
 * @package Minds\Core\Onboarding
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct(
        $manager = null
    ) {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Returns the progress of onboarding
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getProgress(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        /** @var OnboardingGroups\OnboardingGroupInterface */
        $onboardingGroup = $this->manager->setUser($user)
            ->getOnboardingGroup();

        return new JsonResponse(array_merge([
            'status' => 'success',
        ], $onboardingGroup->export()));
    }

    /**
     * Set the seen flag so onboarding doesn't keep getting prompted to the end user
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function setSeen(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $success = $this->manager
            ->setUser($user)
            ->setOnboardingShown(time());

        return new JsonResponse([
            'status' => $success ? 'success' : 'error',
        ]);
    }

    public function claimReward(ServerRequest $request): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Not yet implemented'
        ]);
    }
}
