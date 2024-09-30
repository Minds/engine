<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services;

use Minds\Core\MultiTenant\Bootstrap\Services\BootstrapProgressService;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;
use Minds\Core\Log\Logger;
use PhpSpec\ObjectBehavior;

class BootstrapProgressServiceSpec extends ObjectBehavior
{
    private $progressRepositoryMock;
    private $loggerMock;

    public function let(BootstrapProgressRepository $progressRepository, Logger $logger)
    {
        $this->progressRepositoryMock = $progressRepository;
        $this->loggerMock = $logger;

        $this->beConstructedWith($progressRepository, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BootstrapProgressService::class);
    }

    public function it_should_get_progress()
    {
        $tenantId = null;

        $this->progressRepositoryMock->getProgress(tenantId: $tenantId)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->getProgress(tenantId: $tenantId)->shouldReturn([]);
    }

    public function it_should_handle_error_getting_progress()
    {
        $tenantId = null;

        $this->loggerMock->error('Failed to get bootstrap progress: Error')
            ->shouldBeCalled();

        $this->progressRepositoryMock->getProgress(tenantId: $tenantId)
            ->shouldBeCalled()
            ->willThrow(new \Exception('Error'));

        $this->getProgress(tenantId: $tenantId)->shouldReturn([]);
    }
}
