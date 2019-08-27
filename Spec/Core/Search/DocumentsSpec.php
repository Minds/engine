<?php

namespace Spec\Minds\Core\Search;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DocumentsSpec extends ObjectBehavior
{

    function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Search\Documents');
    }
}
