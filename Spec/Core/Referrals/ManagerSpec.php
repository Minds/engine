<?php

namespace Spec\Minds\Core\Referrals;

use Minds\Core\Referrals\Manager;
use Minds\Core\Referrals\Repository;
use Minds\Core\Referrals\Referral;
use Minds\Core;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $repository;

    function let(Repository $repository) 
    {
        $this->beConstructedWith($repository);
        $this->repository=$repository;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    function it_should_pass_referral_to_repository()
    {
        // $referral = new Referral();
        // $referral->setProspectGuid(Core\Session::getLoggedInUserGuid())
        //     ->setReferrerGuid('1234')
        //     ->setRegisterTimestamp(time());
        // $this->repository->add($referral)
        //     ->shouldBeCalled();
        // $this->add('123')
        //     ->shouldReturn(true);
    }
}

// '$this' is the class of the spec

// * @method void beConstructedWith(...$arguments)
// * @method void beConstructedThrough($factoryMethod, array $constructorArguments = array())
// * @method void beAnInstanceOf($class)
// *
// * @method void shouldHaveType($type)
// * @method void shouldNotHaveType($type)
// * @method void shouldBeAnInstanceOf($type)
// * @method void shouldNotBeAnInstanceOf($type)
// * @method void shouldImplement($interface)
// * @method void shouldNotImplement($interface)
// *
// * @method Subject\Expectation\DuringCall shouldThrow($exception = null)
// * @method Subject\Expectation\DuringCall shouldNotThrow($exception = null)
// * @method Subject\Expectation\DuringCall shouldTrigger($level = null, $message = null)
// *
// * @method void shouldHaveCount($count)
// * @method void shouldNotHaveCount($count)
// *
// * @method void shouldHaveKeyWithValue($key, $value)
// * @method void shouldNotHaveKeyWithValue($key, $value)
// *
// * @method void shouldHaveKey($key)
// * @method void shouldNotHaveKey($key)