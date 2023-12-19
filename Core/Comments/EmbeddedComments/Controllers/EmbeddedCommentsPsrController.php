<?php
namespace Minds\Core\Comments\EmbeddedComments\Controllers;

use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\ServerRequest;

class EmbeddedCommentsPsrController
{
    /**
     * Will try to close the window the current page. Useful for automatic redirects where a parent
     * wants to receive a window close event.
     * @param ServerRequest $request
     * @return RedirectResponse
     */
    public function closeWindow(ServerRequest $request): HtmlResponse
    {
        return new HtmlResponse(
            <<<HTML
<script>window.close();</script>
<p>Please close this window/tab.</p>
HTML
        );
    }
}
