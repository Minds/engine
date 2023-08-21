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

    // @ tags

    // ## @ tags no domain

    public function it_validates_an_at_tag_with_no_domain()
    {
        $testString = '@test';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test');
        expect($results[1])->toBe('test');
    }
    
    public function it_validates_an_upper_case_at_tag_with_no_domain()
    {
        $testString = '@TEST';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@TEST');
        expect($results[1])->toBe('TEST');
    }

    public function it_validates_a_mixed_case_at_tag_with_no_domain()
    {
        $testString = '@TeSt';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@TeSt');
        expect($results[1])->toBe('TeSt');
    }
    
    public function it_validates_an_at_tag_with_numbers_and_no_domain()
    {
        $testString = '@test1';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test1');
        expect($results[1])->toBe('test1');
    }
    
    public function it_validates_an_at_tag_with_hyphen_and_no_domain()
    {
        $testString = '@test1-hyphen';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test1-hyphen');
        expect($results[1])->toBe('test1-hyphen');
    }
    

    public function it_validates_an_at_tag_with_underscores_and_no_domain()
    {
        $testString = '@test_1_underscore';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test_1_underscore');
        expect($results[1])->toBe('test_1_underscore');
    }

    public function it_validates_an_at_tag_with_an_underscore_at_the_end_and_no_domain()
    {
        $testString = '@test_1_underscore_';
        
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test_1_underscore_');
        expect($results[1])->toBe('test_1_underscore_');
    }
    
    public function it_validates_an_at_tag_with_periods_and_no_domain()
    {
        $testString = '@test.1.period';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test.1.period');
        expect($results[1])->toBe('test.1.period');
    }
    
    public function it_validates_an_at_tag_surrounded_by_other_terms_and_no_domain()
    {
        $testString = 'otherTerm1 @test otherTerm2';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe(' @test');
        expect($results[1])->toBe('test');
    }
    
    public function it_should_be_the_first_of_many_at_tags_with_no_domain()
    {
        $testString = 'otherTerm1 @test1 otherTerm2 @test2 1 @test3 @test4@test5';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe(' @test1');
        expect($results[1])->toBe('test1');
    }
    
    public function it_should_ignore_any_hyphen_at_the_end_of_an_at_tag_with_no_domain()
    {
        $testString = '@test-';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test');
        expect($results[1])->toBe('test');
    }
    
    public function it_should_ignore_any_period_at_the_end_of_an_at_tag_with_no_domain()
    {
        $testString = '@test.';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test');
        expect($results[1])->toBe('test');
    }
    
    public function it_should_NOT_validate_an_at_tag_mid_word_with_no_domain()
    {
        $testString = 'hello@test';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results)->toBe([]);
    }

    // // ## @ tags WITH domain

    public function it_should_validate_an_at_tag_with_domain()
    {
        $testString = '@test@minds.com';
        
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test@minds.com');
        expect($results[1])->toBe('test@minds.com');
        expect($results[2])->toBe('minds.com');
    }
    
    public function it_should_validate_an_upper_case_at_tag_with_lowercase_domain()
    {
        $testString = '@TEST@minds.com';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@TEST@minds.com');
        expect($results[1])->toBe('TEST@minds.com');
        expect($results[2])->toBe('minds.com');
    }

    public function it_should_validate_an_upper_case_at_tag_with_uppercase_domain()
    {
        $testString = '@TEST@MINDS.COM';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@TEST@MINDS.COM');
        expect($results[1])->toBe('TEST@MINDS.COM');
        expect($results[2])->toBe('MINDS.COM');
    }
    
    public function it_should_validate_a_mixed_case_at_tag_with_domain()
    {
        $testString = '@TeSt@mInDs.cOm';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@TeSt@mInDs.cOm');
        expect($results[1])->toBe('TeSt@mInDs.cOm');
        expect($results[2])->toBe('mInDs.cOm');
    }

    public function it_should_validate_an_at_tag_with_numbers_and_domain()
    {
        $testString = '@test1@minds1.com';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test1@minds1.com');
        expect($results[1])->toBe('test1@minds1.com');
        expect($results[2])->toBe('minds1.com');
    }
    
    public function it_should_validate_an_at_tag_with_hyphen_in_prefix_and_domain()
    {
        $testString = '@test1-hyphen@minds-test.com';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test1-hyphen@minds-test.com');
        expect($results[1])->toBe('test1-hyphen@minds-test.com');
        expect($results[2])->toBe('minds-test.com');
    }
    
    public function it_should_validate_an_at_tag_with_underscores_and_domain()
    {
        $testString = '@test_1_underscore@minds_test.com';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test_1_underscore@minds_test.com');
        expect($results[1])->toBe('test_1_underscore@minds_test.com');
        expect($results[2])->toBe('minds_test.com');
    }

    public function it_should_validate_an_at_tag_with_an_underscore_at_the_end_and_domain()
    {
        $testString = '@test_1_underscore_@minds.com';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test_1_underscore_@minds.com');
        expect($results[1])->toBe('test_1_underscore_@minds.com');
        expect($results[2])->toBe('minds.com');
    }
    
    public function it_should_validate_an_at_tag_with_periods_and_domain()
    {
        $testString = '@test.1.period@minds.com';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test.1.period@minds.com');
        expect($results[1])->toBe('test.1.period@minds.com');
        expect($results[2])->toBe('minds.com');
    }
    
    public function it_should_validate_an_at_tag_surrounded_by_other_terms_and_domain()
    {
        $testString = 'otherTerm1 @test@minds.com otherTerm2';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe(' @test@minds.com');
        expect($results[1])->toBe('test@minds.com');
        expect($results[2])->toBe('minds.com');
    }

    public function it_should_validate_the_first_of_many_at_tags_with_domain()
    {
        $testString = 'otherTerm1 @test1@minds.com otherTerm2 @test2 1 @test3 @test4@test5';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe(' @test1@minds.com');
        expect($results[1])->toBe('test1@minds.com');
        expect($results[2])->toBe('minds.com');
    }

    public function it_should_ignore_any_hyphen_at_the_end_of_an_at_tag_with_domain()
    {
        $testString = '@test-@minds.com';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test');
        expect($results[1])->toBe('test');
    }

    public function it_should_ignore_any_period_at_the_end_of_an_at_tag_with_domain()
    {
        $testString = '@test.@minds.com';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results[0])->toBe('@test');
        expect($results[1])->toBe('test');
    }
    
    public function it_should_NOT_validate_an_at_tag_mid_word_with_domain()
    {
        $testString = 'hello@test@minds.com';
        $results = [];

        preg_match($this->AT, $testString, $results);

        expect($results)->toBe([]);
    }

    public function it_should_validate_many_at_tags_with_and_without_domain()
    {
        $testString = 'otherTerm1 @test1@minds.com otherTerm2 @test2 1 @test3 @test4@test5';
        $results = [];

        preg_match_all($this->AT, $testString, $results);

        expect($results[0][0])->toBe(' @test1@minds.com');
        expect($results[0][1])->toBe(' @test2');
        expect($results[0][2])->toBe(' @test3');
        expect($results[0][3])->toBe(' @test4');

        expect($results[1][0])->toBe('test1@minds.com');
        expect($results[1][1])->toBe('test2');
        expect($results[1][2])->toBe('test3');
        expect($results[1][3])->toBe('test4');

        expect($results[2][0])->toBe('minds.com');
        expect($results[2][1])->toBe('');
        expect($results[2][2])->toBe('');
        expect($results[2][3])->toBe('');
    }
      
    public function it_should_detect_at_tag_at_start_of_string()
    {
        $this->globalMatch($this->regex::AT, '@minds channel')->shouldReturn(1);
    }

    public function it_should_detect_at_tag_at_end_of_string()
    {
        $this->globalMatch($this->regex::AT, 'channel @minds')->shouldReturn(1);
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

    // hash tags

    public function it_should_detect_hash_tag_at_start_of_string()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '#minds channel')->shouldReturn(1);
    }

    public function it_should_detect_hash_tag_at_end_of_string()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, 'channel #minds')->shouldReturn(1);
    }

    public function it_should_match_multiple_hash_tags_together_no_whitespace()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '#minds#minds')->shouldReturn(2);
    }

    public function it_should_pick_hash_tag_out_mid_word()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, 'test#minds')->shouldReturn(1);
    }

    public function it_should_match_multiple_hash_tags_sequentially_with_space()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '#minds #minds')->shouldReturn(2);
    }

    public function it_should_match_multiple_hash_tags_seperated_by_a_word()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '#minds minds #minds')->shouldReturn(2);
    }

    public function it_should_match_multiple_hash_tags_on_multiple_lines()
    {
        $this->globalMatch(
            $this->regex::HASH_CASH_TAG,
            "#minds 
            minds
            #minds"
        )->shouldReturn(2);

        $this->globalMatch(
            $this->regex::HASH_CASH_TAG,
            "asd #minds 
            minds asd
            asd #minds asd"
        )->shouldReturn(2);
    }

    public function it_should_match_hash_tags_with_suffixed_punctuation()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '#minds. #minds!')->shouldReturn(2);
        $this->globalMatch($this->regex::HASH_CASH_TAG, '#minds? #minds@')->shouldReturn(2);
        $this->globalMatch($this->regex::HASH_CASH_TAG, '#minds; #minds:')->shouldReturn(2);
    }

    public function it_should_pick_many_hash_tags_out_of_string()
    {
        $this->globalMatch(
            $this->regex::HASH_CASH_TAG,
            '#ab test #bc testing test #d4 #23 #asd #vxc #gdf #9fui #testing #123 #idsj'
        )->shouldReturn(11);
    }

    // cash tags ($)

    public function it_should_detect_cash_tag_at_start_of_string()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds channel')->shouldReturn(1);
    }

    public function it_should_detect_cash_tag_at_end_of_string()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, 'channel $minds')->shouldReturn(1);
    }

    public function it_should_match_multiple_cash_tags_together_no_whitespace()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds$minds')->shouldReturn(2);
    }

    public function it_should_pick_cash_tag_out_mid_word()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, 'test$minds')->shouldReturn(1);
    }

    public function it_should_match_multiple_cash_tags_sequentially_with_space()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds $minds')->shouldReturn(2);
    }

    public function it_should_match_multiple_cash_tags_seperated_by_a_word()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '#minds minds #minds')->shouldReturn(2);
    }

    public function it_should_match_multiple_cash_tags_on_multiple_lines()
    {
        $this->globalMatch(
            $this->regex::HASH_CASH_TAG,
            "\$minds 
            minds
            \$minds"
        )->shouldReturn(2);

        $this->globalMatch(
            $this->regex::HASH_CASH_TAG,
            "asd \$minds 
            minds asd
            asd \$minds asd"
        )->shouldReturn(2);
    }

    public function it_should_match_cash_tags_with_suffixed_punctuation()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds. $minds!')->shouldReturn(2);
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds? $minds@')->shouldReturn(2);
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds; $minds:')->shouldReturn(2);
    }

    public function it_should_pick_many_cash_tags_out_of_string()
    {
        $this->globalMatch(
            $this->regex::HASH_CASH_TAG,
            '$ab test $bc testing test $d4 $23 $asd $vxc $gdf $9fui $testing $123 $idsj'
        )->shouldReturn(8);
    }


    // hash AND cash tags ($ | #)

    public function it_should_match_multiple_cash__and_hash_tags_together_no_whitespace()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds#minds')->shouldReturn(2);
    }

    // public function it_should_pick_cash_and_hash_tags_out_mid_word()
    // {
    //     $this->globalMatch($this->regex::HASH_CASH_TAG, 'test#minds$usd')->shouldReturn(2);
    // }

    public function it_should_match_multiple_cash_and_hash_tags_sequentially_with_space()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds $minds #minds #minds')->shouldReturn(4);
    }

    public function it_should_match_multiple_cash_and_hash_tags_seperated_by_a_word()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '#minds minds $minds')->shouldReturn(2);
    }

    public function it_should_match_multiple_cash_and_hash_tags_on_multiple_lines()
    {
        $this->globalMatch(
            $this->regex::HASH_CASH_TAG,
            "\$minds 
            minds
            #minds"
        )->shouldReturn(2);

        $this->globalMatch(
            $this->regex::HASH_CASH_TAG,
            "asd \$minds 
            minds asd
            asd #minds asd"
        )->shouldReturn(2);
    }

    public function it_should_match_cash_and_hash_tags_with_suffixed_punctuation()
    {
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds. #minds!')->shouldReturn(2);
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds? #minds@')->shouldReturn(2);
        $this->globalMatch($this->regex::HASH_CASH_TAG, '$minds; #minds:')->shouldReturn(2);
    }

    public function it_should_pick_many_cash_and_hash_tags_out_of_string()
    {
        $this->globalMatch(
            $this->regex::HASH_CASH_TAG,
            '$ab test #bc testing test $d4 #23 $asd $vxc #gdf #9fui $testing $123 $idsj'
        )->shouldReturn(10);
    }
    
    public function it_should_NOT_match_numeric_cashtags()
    {
        $this->globalMatch(
            $this->regex::HASH_CASH_TAG,
            '$20,000 $3d $23'
        )->shouldReturn(0);
    }
}
