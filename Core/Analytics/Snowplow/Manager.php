<?php
namespace Minds\Core\Analytics\Snowplow;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Snowplow\Tracker\Tracker;
use Snowplow\Tracker\Subject;
use Snowplow\Tracker\Emitters\CurlEmitter;

class Manager
{
    /** @var CurlEmitter */
    private $emitter;

    /** @var Subject */
    protected $subject;

    /** @var Tracker */
    protected $tracker;

    public function __construct($emitter = null, $config = null)
    {
        $config = $config ?? Di::_()->get('Config');
        $this->emitter = new CurlEmitter($config->get('snowplow')['collector_uri'], $config->get('snowplow')['proto'] ?? 'https', "POST", 2, true);
    }

    /**
     * Sets the subject (user) to record the analytics with
     * @param User $user
     * @return Manager
     */
    public function setSubject(User $user = null): Manager
    {
        // Subject
        $subject = new Subject();
        
        $subject->setIpAddress($_SERVER['HTTP_X_FORWARDED_FOR'] ?? null);
        $subject->setPlatform($this->getPlatform());
        $subject->setUseragent($_SERVER['HTTP_USER_AGENT'] ?? null);
        
        if ($user) {
            $subject->setUserId($user->getGuid());
            $subject->setLanguage($user->getLanguage());
        }

        // Tracker
        $tracker = new Tracker($this->emitter, $subject, 'ma', 'minds');

        // Manager Clone / Setup
        $manager = clone $this;
        $manager->subject = $subject;
        $manager->tracker = $tracker;

        return $manager;
    }

    /**
     * Emit an event
     * @param SnowplowEventInterface $event
     * @return void
     */
    public function emit(Events\SnowplowEventInterface $event): void
    {
        $this->tracker->trackUnstructEvent(
            [
                'schema' => $event->getSchema(),
                'data' => $event->getData(),
            ],
            array_values(
                array_filter(
                    array_map(function ($context) {
                        if (empty($context->getData())) {
                            return null;
                        }
                        return [
                            'schema' => $context->getSchema(),
                            'data' => $context->getData(),
                        ];
                    }, $event->getContext() ?: [])
                )
            ),
        );
       
        $this->tracker->flushEmitters();
    }

    /**
     * Return the platform
     * @return string
     */
    protected function getPlatform(): string
    {
        $platform = $_REQUEST['platform'] ?? 'web';
        if ($platform === 'browser') {
            $platform = 'web';
        }
        return $platform;
    }
}
