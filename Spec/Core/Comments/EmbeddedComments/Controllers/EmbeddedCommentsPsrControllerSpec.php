<?php

namespace Spec\Minds\Core\Comments\EmbeddedComments\Controllers;

use Minds\Core\Comments\EmbeddedComments\Controllers\EmbeddedCommentsPsrController;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\ServerRequest;

class EmbeddedCommentsPsrControllerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(EmbeddedCommentsPsrController::class);
    }

    public function it_should_close_window_with_js()
    {
        $this->closeWindow(new ServerRequest())->shouldBeAnInstanceOf(HtmlResponse::class);
    }
}
