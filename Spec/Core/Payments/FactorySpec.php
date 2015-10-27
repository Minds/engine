<?php

namespace Spec\Minds\Core\Payments;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Config;

class FactorySpec extends ObjectBehavior{

  function let(){
    Config::_()->payments = [
      'braintree' => [
        'environment' => 'sandbox',
        'merchant_id' => Argument::any(),
        'public_key' => Argument::any(),
        'private_key' => Argument::any()
      ]
    ];
  }

  function it_is_initializable(){
    $this->shouldHaveType('Minds\Core\Payments\Factory');
  }

  function it_should_build_a_service(){
    $this::build('braintree')->shouldImplement('Minds\Core\Payments\PaymentServiceInterface');
  }

  function it_should_throw_an_exception_if_service_doesnt_exist(){
    $this->shouldThrow('\Exception')->during('build', ['foobar']);
  }

}
