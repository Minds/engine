<?php
namespace Minds\Core\Analytics\Snowplow;

use Minds\Common\PseudonymousIdentifier;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Snowplow\Tracker\Emitters\CurlEmitter;
use Snowplow\Tracker\Subject;
use Snowplow\Tracker\Tracker;

class Manager
{
    /** @var CurlEmitter */
    private $emitter;

    /** @var Subject */
    protected $subject;

    /** @var Tracker */
    protected $tracker;

    public function __construct($emitter = null, $config = null, protected ?PseudonymousIdentifier $pseudonymousIdentifier = null)
    {
        $config = $config ?? Di::_()->get('Config');
        $this->emitter = $emitter ?? new CurlEmitter($config->get('snowplow')['collector_uri']?? '', $config->get('snowplow')['proto'] ?? 'https', "POST", 2, false);
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
        $subject->setNetworkUserId($_COOKIE['minds_sp'] ?? null);
        
        if ($user) {
            /**
             * We do not apply the user_guid, instead we supply a pseudonymous identifier that
             * can only be created by hashing the user_guid with their password at the point of authentication.
             *
             * We currently fallback to a user_guid for server side action events **ONLY** and never for observational
             * analytics such as pageviews, which are always pseudonymised.
             */
            $subject->setUserId($this->pseudonymousIdentifier?->setUser($user)->getId() ?: $user->getGuid());
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
            )
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
