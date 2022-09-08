<?php

namespace Spec\Minds\Core\Entities\EventStreams;

use Minds\Core\Blogs\Blog;
use Minds\Core\Entities\EventStreams\MetricChangeStreamsSubscription;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;
use Minds\Core\Sockets\Events as SocketEvents;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Entities\Image;
use Minds\Entities\Video;
use Minds\Core\Counters;
use Minds\Core\Log\Logger;
use Prophecy\Argument;

class MetricChangeStreamsSubscriptionSpec extends ObjectBehavior
{
    /** @var SocketEvents */
    private $socketEvents;

    /** @var Counters */
    private $counters;

    /** @var ExperimentsManager */
    private $experiments;

    /** @var Logger */
    private $logger;

    public function let(
        SocketEvents $socketEvents,
        Counters $counters,
        ExperimentsManager $experiments,
        Logger $logger
    ) {
        $this->socketEvents = $socketEvents;
        $this->counters = $counters;
        $this->experiments = $experiments;
        $this->logger = $logger;

        $this->beConstructedWith($socketEvents, $counters, $experiments, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MetricChangeStreamsSubscription::class);
    }

    // EXPERIMENT

    public function it_should_NOT_emit_event_if_experiment_is_off(ActionEvent $event, Activity $entity)
    {
        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->socketEvents->emit(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->consume($event);
    }

    // ACTIVITY

    public function it_should_emit_event_for_vote_up_for_an_activity(ActionEvent $event, Activity $entity)
    {
        $guid = '123';
        $entityGuid = null;

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);
            
        $entity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_UP);

        $this->counters->get('123', 'thumbs:up', false)
            ->shouldBeCalled()
            ->willReturn(1);
       
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:up:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_down_for_an_activity(ActionEvent $event, Activity $entity)
    {
        $guid = '123';
        $entityGuid = null;

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);
            
        $entity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_DOWN);
       
        $this->counters->get('123', 'thumbs:down', false)
            ->shouldBeCalled()
            ->willReturn(1);

        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:down:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_up_removal_for_an_activity(ActionEvent $event, Activity $entity)
    {
        $guid = '123';
        $entityGuid = null;

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);
            
        $entity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_UP_REMOVED);

        $this->counters->get('123', 'thumbs:up', false)
            ->shouldBeCalled()
            ->willReturn(1);

        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:up:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_down_removed_for_an_activity(ActionEvent $event, Activity $entity)
    {
        $guid = '123';
        $entityGuid = null;

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);
            
        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_DOWN_REMOVED);

        $this->counters->get('123', 'thumbs:down', false)
            ->shouldBeCalled()
            ->willReturn(1);

        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:down:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    // ACTIVITY WITH LINKED MEDIA
    
    public function it_should_emit_event_for_vote_up_for_an_activity_with_linked_media(ActionEvent $event, Activity $entity)
    {
        $entityGuid = '321';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_UP);

        $this->counters->get($entityGuid, 'thumbs:up', false)
            ->shouldBeCalled()
            ->willReturn(1);
        
        $this->socketEvents->setRoom("entity:metrics:$entityGuid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$entityGuid", '{"thumbs:up:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_down_for_an_activity_with_linked_media(ActionEvent $event, Activity $entity)
    {
        $entityGuid = '321';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_DOWN);

        $this->counters->get($entityGuid, 'thumbs:down', false)
            ->shouldBeCalled()
            ->willReturn(1);
        
        $this->socketEvents->setRoom("entity:metrics:$entityGuid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$entityGuid", '{"thumbs:down:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_up_removal_for_an_activity_with_linked_media(ActionEvent $event, Activity $entity)
    {
        $entityGuid = '321';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_UP_REMOVED);

        $this->counters->get($entityGuid, 'thumbs:up', false)
            ->shouldBeCalled()
            ->willReturn(1);

        $this->socketEvents->setRoom("entity:metrics:$entityGuid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$entityGuid", '{"thumbs:up:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_down_removed_for_an_activity_with_linked_media(ActionEvent $event, Activity $entity)
    {
        $entityGuid = '321';
         
        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getEntityGuid()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_DOWN_REMOVED);
        
        $this->counters->get('321', 'thumbs:down', false)
            ->shouldBeCalled()
            ->willReturn(1);
       
        $this->socketEvents->setRoom("entity:metrics:$entityGuid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$entityGuid", '{"thumbs:down:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    // IMAGE

    public function it_should_emit_event_for_vote_up_for_an_image(ActionEvent $event, Image $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_UP);
        
        $this->counters->get($guid, 'thumbs:up', false)
            ->shouldBeCalled()
            ->willReturn(1);
       
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:up:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_down_for_an_image(ActionEvent $event, Image $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_DOWN);
        
        $this->counters->get($guid, 'thumbs:down', false)
            ->shouldBeCalled()
            ->willReturn(1);
       
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:down:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_up_removal_for_an_image(ActionEvent $event, Image $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_UP_REMOVED);
        
        $this->counters->get($guid, 'thumbs:up', false)
            ->shouldBeCalled()
            ->willReturn(1);
       
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:up:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_down_removed_for_an_image(ActionEvent $event, Image $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_DOWN_REMOVED);
            
        $this->counters->get($guid, 'thumbs:down', false)
            ->shouldBeCalled()
            ->willReturn(1);
       
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:down:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    // Video

    public function it_should_emit_event_for_vote_up_for_a_video(ActionEvent $event, Video $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);
        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_UP);

        $this->counters->get($guid, 'thumbs:up', false)
            ->shouldBeCalled()
            ->willReturn(1);
        
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:up:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_down_for_a_video(ActionEvent $event, Video $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_DOWN);
            
        $this->counters->get($guid, 'thumbs:down', false)
            ->shouldBeCalled()
            ->willReturn(1);
       
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:down:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_up_removal_for_a_video(ActionEvent $event, Video $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_UP_REMOVED);

        $this->counters->get($guid, 'thumbs:up', false)
            ->shouldBeCalled()
            ->willReturn(1);
        
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:up:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_down_removed_for_a_video(ActionEvent $event, Video $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);
            
        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_DOWN_REMOVED);
            
        $this->counters->get($guid, 'thumbs:down', false)
            ->shouldBeCalled()
            ->willReturn(1);
       
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:down:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    // Blog

    public function it_should_emit_event_for_vote_up_for_a_blog(ActionEvent $event, Blog $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_UP);
            
        $this->counters->get($guid, 'thumbs:up', false)
            ->shouldBeCalled()
            ->willReturn(1);
        
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:up:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_down_for_a_blog(ActionEvent $event, Blog $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);
            
        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_DOWN);
           
        $this->counters->get($guid, 'thumbs:down', false)
            ->shouldBeCalled()
            ->willReturn(1);
        
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:down:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_up_removal_for_a_blog(ActionEvent $event, Blog $entity)
    {
        $guid = '123';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);
            
        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_UP_REMOVED);
            
        $this->counters->get($guid, 'thumbs:up', false)
            ->shouldBeCalled()
            ->willReturn(1);
        
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:up:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }

    public function it_should_emit_event_for_vote_down_removed_for_a_blog(ActionEvent $event, Blog $entity)
    {
        $guid = '321';

        $this->experiments->isOn('engine-1218-metrics-sockets')
            ->shouldBeCalled()
            ->willReturn(true);

        $entity->getGuid()
            ->shouldBeCalled()
            ->willReturn($guid);

        $event->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $event->getAction()
            ->willReturn(ActionEvent::ACTION_VOTE_DOWN_REMOVED);
            
        $this->counters->get($guid, 'thumbs:down', false)
            ->shouldBeCalled()
            ->willReturn(1);
        
        $this->socketEvents->setRoom("entity:metrics:$guid")
            ->shouldBeCalled()
            ->willReturn($this->socketEvents);

        $this->socketEvents->emit("entity:metrics:$guid", '{"thumbs:down:count":1}')
            ->shouldBeCalled();

        $this->consume($event);
    }
}
