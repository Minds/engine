<?php

namespace Spec\Minds\Common;

use Minds\Entities\Activity;
use Minds\Entities\MutatableEntityInterface;
use Minds\Common\Regex;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RegexSpec extends ObjectBehavior
{
    public $regex;

    public function let()
    {
        $this->regex = new Regex();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Regex::class);
    }

    public function it_should_detect_at_tag_at_start_of_string()
    {
        $this->globalMatch($this->regex::AT, '@minds channel')->shouldReturn(1);
    }

    public function it_should_detect_at_tag_at_end_of_string()
    {
        $this->globalMatch($this->regex::AT, 'channel @minds')->shouldReturn(1);
    }

    public function it_should_match_multiple_tags_together_no_whitespace()
    {
        $this->globalMatch($this->regex::AT, '@minds@minds')->shouldReturn(2);
    }

    public function it_should_pick_a_tag_out_mid_word()
    {
        $this->globalMatch($this->regex::AT, 'test@minds')->shouldReturn(1);
    }

    public function it_should_match_multiple_tags_sequentially_with_space()
    {
        $this->globalMatch($this->regex::AT, '@minds @minds')->shouldReturn(2);
    }

    public function it_should_match_multiple_tags_seperated_by_a_word()
    {
        $this->globalMatch($this->regex::AT, '@minds minds @minds')->shouldReturn(2);
    }

    public function it_should_match_multiple_tags_on_multiple_lines()
    {
        $this->globalMatch(
            $this->regex::AT,
            "@minds 
            minds
            @minds"
        )->shouldReturn(2);

        $this->globalMatch(
            $this->regex::AT,
            "asd @minds 
            minds asd
            asd @minds asd"
        )->shouldReturn(2);
    }

    public function it_should_match_numeric_and_underscore_tags()
    {
        $this->globalMatch($this->regex::AT, '@3_2_1 minds @1_23')->shouldReturn(2);
    }

    public function it_should_match_tags_with_suffixed_punctuation()
    {
        $this->globalMatch($this->regex::AT, '@minds. @minds!')->shouldReturn(2);
        $this->globalMatch($this->regex::AT, '@minds? @minds@')->shouldReturn(2);
        $this->globalMatch($this->regex::AT, '@minds; @minds:')->shouldReturn(2);
    }

    public function it_should_pick_many_users_out_of_string()
    {
        $this->globalMatch(
            $this->regex::AT,
            '@ab test @bc testing test @d4 @23 @asd @vxc @gdf @9fui @testing @123 @idsj'
        )->shouldReturn(11);
    }
}
