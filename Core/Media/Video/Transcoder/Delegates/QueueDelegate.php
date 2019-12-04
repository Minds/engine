<?php
namespace Minds\Core\Media\Video\Transcoder\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Minds\Core\Queue\Interfaces\QueueClient;

class QueueDelegate
{
    /** @var QueueClient */
    private $queueClient;

    public function __construct($queueClient = null)
    {
        $this->queueClient = $queueClient ?? Di::_()->get('Queue');
    }

    /**
     * Add a transcode to the queue
     * @param Transcode $transcode
     * @return void
     */
    public function onAdd(Transcode $transcode): void
    {
        $this->queueClient
            ->setQueue('Transcode')
            ->send([
                'transcode' => serialize($transcode),
            ]);
    }
}
