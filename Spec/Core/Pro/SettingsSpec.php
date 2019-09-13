<?php

namespace Spec\Minds\Core\Pro;

use Minds\Core\Pro\Settings;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SettingsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Settings::class);
    }

    public function it_should_get_one_line_headline_from_single_line_value()
    {
        $this->setHeadline('This is a headline');

        $this
            ->getOneLineHeadline()
            ->shouldReturn('This is a headline');
    }

    public function it_should_get_one_line_headline_from_multi_line_value()
    {
        $this->setHeadline("This is a headline.\nOther line");

        $this
            ->getOneLineHeadline()
            ->shouldReturn('This is a headline. Other line');
    }

    public function it_should_export()
    {
        $this
            ->export()
            ->shouldBeArray();
    }

    public function it_should_build_styles()
    {
        $this
            ->buildStyles()
            ->shouldBeArray();
    }

    public function it_should_calc_tile_ratio_percentage()
    {
        $this
            ->setTileRatio('1:1');

        $this
            ->calcTileRatioPercentage()
            ->shouldReturn(100.0);
        
        $this
            ->setTileRatio('4:3');

        $this
            ->calcTileRatioPercentage()
            ->shouldReturn(75.0);
        
        $this
            ->setTileRatio('16:10');

        $this
            ->calcTileRatioPercentage()
            ->shouldReturn(62.5);
        
        $this
            ->setTileRatio('16:9');

        $this
            ->calcTileRatioPercentage()
            ->shouldReturn(56.25);
        
        $this
            ->setTileRatio('');

        $this
            ->calcTileRatioPercentage()
            ->shouldReturn(56.25);
    }
}
