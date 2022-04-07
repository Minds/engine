<?php
namespace Minds\Core\Captcha\FriendlyCaptcha;

use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\InvalidSolutionException;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Controller for FriendlyCaptcha.
 */
class Controller
{
    const DEBUG = false; // TODO: Disable

    /**
     * Controller constructor.
     * @param ?Manager $manager - FriendlyCaptcha manager.
     */
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= new Manager();
    }

    /**
     * Generate a puzzle and return it for consumption by widget.
     * @throws MisconfigurationException - if server misconfigured.
     * @return JsonResponse - response for consumption by widget object.
     */
    public function generatePuzzle(): JsonResponse
    {
        $puzzle = $this->manager->generatePuzzle();

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'puzzle' => $puzzle,
            ]
        ]);
    }

    /**
     * Debug function to manually verify a solution.
     * @param ServerRequest $request - request object.
     * @throws SolutionAlreadySeenException - if individual solution has already been seen.
     * @throws PuzzleReusedException - if proposed puzzle solution has been reused.
     * @throws InvalidSolutionException - if solution is invalid.
     * @return JsonResponse - success state.
     */
    public function verifySolution(ServerRequest $request): JsonResponse
    {
        if (!static::DEBUG) {
            throw new ForbiddenException();
        }

        $body = $request->getParsedBody();
        $solution = $body['solution'] ?? '';

        if (!$solution) {
            throw new UserErrorException('No CAPTCHA solution provided');
        }

        if ($this->manager->verify($solution)) {
            return new JsonResponse([
                'status' => 'success',
            ]);
        }

        throw new InvalidSolutionException();
    }
}
