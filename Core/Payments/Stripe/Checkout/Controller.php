<?php
namespace Minds\Core\Payments\Stripe\Checkout;

use Minds\Core\Di\Di;
use Minds\Core\Router\Enums\RequestAttributeEnum;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(private ?Manager $manager = null)
    {
        $this->manager ??= Di::_()->get('Stripe\Checkout\Manager');
    }

    /**
     * Takes a user to the setup step of stripe checkout
     * @param ServerRequest $request
     * @return RedirectResponse
     */
    public function redirectToSetup(ServerRequest $request): RedirectResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');
    
        $link = $this->manager->createSession($user, 'setup')->url;
    
        return new RedirectResponse($link);
    }

    /**
     * Will try to close the window the current page. Useful for automatic redirects where a parent
     * wants to receive a window close event.
     * @param ServerRequest $request
     * @return RedirectResponse
     */
    public function closeWindow(ServerRequest $request): HtmlResponse
    {
        $cspNonce = $request->getAttribute(RequestAttributeEnum::CSP_NONCE);
        return new HtmlResponse(
            <<<HTML
<script nonce="$cspNonce">window.close();</script>
<p>Please close this window/tab.</p>
HTML
        );
    }
}
