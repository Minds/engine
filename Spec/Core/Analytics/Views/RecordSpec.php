<?php

namespace Spec\Minds\Core\Analytics\Views;

use Minds\Core\Analytics\Views\Record;
use PhpSpec\ObjectBehavior;

class RecordSpec extends ObjectBehavior
{
    public function is_it_intializable()
    {
        $this->shouldHaveType(Record::class);
    }

    public function it_should_set_client_meta()
    {
        $this->setClientMeta([
                'page_token' => 'page_token_value',
                'position' => 'position_value',
                'platform' => 'platform_value',
                'source' => 'source_value',
                'medium' => 'medium_value',
                'campaign' => 'campaign_value',
                'delta' => 'delta_value',
            ])->shouldReturn($this);
    }

    public function it_should_set_identifier()
    {
        $this->setIdentifier('id_1')->shouldReturn($this);
    }
}
