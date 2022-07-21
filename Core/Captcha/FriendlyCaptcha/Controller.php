<?php
namespace Minds\Core\Captcha\FriendlyCaptcha;

use Minds\Core\Captcha\FriendlyCaptcha\Exceptions\InvalidSolutionException;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Controller for FriendlyCaptcha.
 */
class Controller
{
    /** @var boolean */
    const DEBUG = false; // TODO: Keep disabled

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
     * @param ServerRequestInterface $request
     * @return JsonResponse - response for consumption by widget object.
     * @throws Exceptions\MisconfigurationException
     */
    public function generatePuzzle(ServerRequestInterface $request): JsonResponse
    {
        $puzzleOrigin = $request->getQueryParams()['origin'] ?? "";
        $puzzle = $this->manager->generatePuzzle($puzzleOrigin);

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
