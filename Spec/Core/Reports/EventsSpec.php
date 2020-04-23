<?php

namespace Spec\Minds\Core\Reports;

use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Reports\Events;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Config;

class EventsSpec extends ObjectBehavior
{
    /** @var EventsDispatcher */
    protected $dispatcher;
    protected $config;
    protected $banReasons;

    public function let(EventsDispatcher $dispatcher, Config $config)
    {
        Di::_()->bind('EventsDispatcher', function ($di) use ($dispatcher) {
            return $dispatcher->getWrappedObject();
        });
        $this->dispatcher = $dispatcher;
        $this->config = $config;
        $this->banReasons = [
            [
              'value' => 1,
              'label' => 'Illegal',
              'hasMore' => true,
              'reasons' => [
                [ 'value' => 1, 'label' => 'Terrorism' ],
                [ 'value' => 2, 'label' => 'Paedophilia' ],
                [ 'value' => 3, 'label' => 'Extortion' ],
                [ 'value' => 4, 'label' => 'Fraud' ],
                [ 'value' => 5, 'label' => 'Revenge Porn' ],
                [ 'value' => 6, 'label' => 'Sex trafficking' ],
              ],
            ],
            [
              'value' => 2,
              'label' => 'NSFW (not safe for work)',
              'hasMore' => true,
              'reasons' => [ // Explicit reasons
                [ 'value' => 1, 'label' => 'Nudity' ],
                [ 'value' => 2, 'label' => 'Pornography' ],
                [ 'value' => 3, 'label' => 'Profanity' ],
                [ 'value' => 4, 'label' => 'Violance and Gore' ],
                [ 'value' => 5, 'label' => 'Race, Religion, Gender' ],
              ],
            ],
            [
              'value' => 3,
              'label' => 'Encourages or incites violence',
              'hasMore' => false,
            ],
            [
              'value' => 4,
              'label' => 'Harassment',
              'hasMore' => false,
            ],
            [
              'value' => 5,
              'label' => 'Personal and confidential information',
              'hasMore' => false,
            ],
            [
              'value' => 7,
              'label' => 'Impersonates',
              'hasMore' => false,
            ],
            [
              'value' => 8,
              'label' => 'Spam',
              'hasMore' => false,
            ],
            [
              'value' => 10,
              'label' => 'Infringes my copyright',
              'hasMore' => true,
            ],
            [
              'value' => 12,
              'label' => 'Incorrect use of hashtags',
              'hasMore' => false,
            ],
            [
              'value' => 13,
              'label' => 'Malware',
              'hasMore' => false,
            ],
            [
              'value' => 15,
              'label' => 'Trademark infringement',
              'hasMore' => false,
            ],
            [
              'value' => 16,
              'label' => 'Token manipulation',
              'hasMore' => false,
            ],
            [
              'value' => 11,
              'label' => 'Another reason',
              'hasMore' => true,
            ],
          ]
        ;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Events::class);
    }

    public function it_should_register_the_user_ban_event()
    {
        $this->dispatcher->register('ban', 'user', Argument::any())
            ->shouldBeCalled();

        $this->register();
    }


    public function it_should_discern_ban_reason_text()
    {
        Di::_()->get('Config')->set('report_reasons', $this->banReasons);

        $this->getBanReasons(1)
            ->shouldReturn("Illegal");
        
        $this->getBanReasons("1.3")
            ->shouldReturn("Illegal / Extortion");
                
        $this->getBanReasons("2.3")
            ->shouldReturn("NSFW (not safe for work) / Profanity");

        $this->getBanReasons("3")
            ->shouldReturn("Encourages or incites violence");

        $this->getBanReasons("8")
            ->shouldReturn("Spam");

        $this->getBanReasons("because reasons")
            ->shouldReturn("because reasons");
    }
}
