<?php

namespace Spec\Minds\Helpers;

use Minds\Helpers\Env;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Dotenv;

class EnvSpec extends ObjectBehavior
{
    //Loads env from the same directory as this test
    public function let()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        $dotenv->required('MINDS_ENV_test_int')->isInteger();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Env::class);
    }

    public function it_gets_minds_env()
    {
        $config = Env::getMindsEnv();
        expect($config["test"])->toBe('derp');
        expect($config["test_int"])->toBe(4);
        expect($config["test_float"])->toBe(4.45);
        expect($config["test_true"])->toBe(true);
        expect($config["test_false"])->toBe(false);
        expect($config["nested"])->shouldBeArray();
        expect($config["nested"]["array"])->toBe('test');
    }

    public function it_casts_bools()
    {
        expect(Env::cast('true'))->toBe(true);
        expect(Env::cast('false'))->toBe(false);
        expect(Env::cast('trUe'))->toBe(true);
        expect(Env::cast('False'))->toBe(false);
    }

    public function it_casts_ints()
    {
        expect(Env::cast('0'))->toBe(0);
        expect(Env::cast('1'))->toBe(1);
        expect(Env::cast('-99'))->toBe(-99);
    }

    public function it_casts_floats()
    {
        expect(Env::cast('0.05'))->toBe(.05);
        expect(Env::cast('1.2e3'))->toBe(1200);
        expect(Env::cast('-4.34'))->toBe(-4.34);
    }

    // public function it_handles_errors_and_invalid_types()
    // {
    //     expect(Env::cast(null))->toBe(null);
    //     expect(Env::cast([]))->toBe([]);
    //     $this->shouldThrow(\Exception::class)
    //         ->during('cast', [(object)[]]);
    // }

    public function it_should_nest_arrays()
    {
        $testPieces = [0, 1, 2, 3, 4];
        $nestedArray = Env::nestArray($testPieces, 'value');
        expect($nestedArray)->shouldBeArray();
        expect($nestedArray[0][1][2][3][4])->shouldBe('value');
        $testPieces = ['a', 'b', 'c', 'd', 'e'];
        expect($nestedArray)->shouldBeArray();
        $nestedArray = Env::nestArray($testPieces, 'value');
        expect($nestedArray['a']['b']['c']['d']['e'])->shouldBe('value');
    }
}
