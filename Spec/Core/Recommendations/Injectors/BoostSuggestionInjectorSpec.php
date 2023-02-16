<?php

namespace Spec\Minds\Core\Recommendations\Injectors;

use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Log\Logger;
use Minds\Core\Recommendations\Injectors\BoostSuggestionInjector;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class BoostSuggestionInjectorSpec extends ObjectBehavior
{
    private Collaborator $boostManager;
    private Collaborator $logger;

    public function let(
        BoostManager $boostManager,
        Logger $logger
    ) {
        $this->boostManager = $boostManager;
        $this->logger = $logger;
        $this->beConstructedWith($this->boostManager, $this->logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BoostSuggestionInjector::class);
    }
}
