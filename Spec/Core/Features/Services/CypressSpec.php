<?php

namespace Spec\Minds\Core\Features\Services;

use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config as CoreConfig;
use Minds\Core\Features\Services\Cypress;
use Minds\Core\Log\Logger;
use PhpSpec\ObjectBehavior;

class CypressSpec extends ObjectBehavior
{
    /** @var Jwt */
    protected $jwt;

    /** @var Logger */
    protected $logger;

    /** @var Config */
    protected $config;

    public function let(
        CoreConfig $config,
        Logger $logger,
        Jwt $jwt
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->jwt = $jwt;
        $this->beConstructedWith($jwt, $logger, $config);

        $this->config->get('cypress')
            ->shouldBeCalled()
            ->willReturn([
                'shared_key' => '~sharedKey~'
            ]);

        $this->jwt->setKey('~sharedKey~')
            ->shouldBeCalled()
            ->willReturn($this->jwt);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Cypress::class);
    }

    public function it_should_get_readable_name()
    {
        $this->getReadableName()->shouldReturn('Cookie');
    }

    public function it_should_have_noop_for_sync()
    {
        $this->sync(123)->shouldReturn(true);
    }

    public function it_should_fetch()
    {
        $_COOKIE['force_experiment_variations'] = 'ey123';

        $this->jwt->decode('ey123')
            ->shouldBeCalled()
            ->willReturn([
                'data' => [
                    'testExp' => true
                ]
            ]);

        $this->fetch([])->shouldReturn([
            'testExp' => true
        ]);
    }

    public function it_should_log_error_if_fetch_fails()
    {
        $_COOKIE['force_experiment_variations'] = 'ey123';

        $this->jwt->decode('ey123')
            ->shouldBeCalled()
            ->willThrow(new Exception('~error~'));

        $this->logger->error('~error~');
        $this->fetch([])->shouldReturn([]);
    }
}
