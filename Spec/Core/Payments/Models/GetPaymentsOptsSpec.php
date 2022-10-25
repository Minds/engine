<?php

namespace Spec\Minds\Core\Payments\Models;

use Minds\Core\Payments\Models\GetPaymentsOpts;
use PhpSpec\ObjectBehavior;

class GetPaymentsOptsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(GetPaymentsOpts::class);
    }

    public function it_should_export()
    {
        $customerId = '~customerId~';
        $startingAfter = 'pay_123';
        $limit = 4;

        $this->setCustomerId($customerId)
            ->setStartingAfter($startingAfter)
            ->setLimit($limit);

        $this->export()->shouldBe([
            'customer' => $customerId,
            'starting_after' => $startingAfter,
            'limit' => $limit
        ]);
    }
}
