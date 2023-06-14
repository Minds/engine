<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Interfaces;
use Minds\Core\Media\Video\CloudflareStreams\Webhooks;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\Video;
use Zend\Diactoros\ServerRequest;

class Cloudflare extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?ACL $acl = null,
        private ?Webhooks $cloudflareStreamsWebhooks = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->acl ??= Di::_()->get('Security\ACL');
        $this->cloudflareStreamsWebhooks ??= Di::_()->get('Media\Video\CloudflareStreams\Webhooks');
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: cli trending <type>');
    }

    public function exec()
    {
    }

    public function registerWebhook()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $secret = $this->cloudflareStreamsWebhooks->registerWebhook();

        $this->out('Your secret is ' . $secret . ' - Save this to settings.php');
    }
    
    /**
     * Manually call Cloudflare webhook. Useful for testing or manually altering a videos state / dimensions.
     * @param string videoGuid - guid of video to process.
     * @param string height - video height.
     * @param string width - video width.
     * @param string state - state of video, defaults to ready.
     * @example
     * -  php cli.php Cloudflare onWebhookOverride --videoGuid=1234567890 --height=400 --width=300 --state=ready
     * -  php cli.php Cloudflare onWebhookOverride --videoGuid=1234567890 --height=400 --width=300
     * @return void
     */
    public function onWebhookOverride(): void
    {
        $videoGuid = $this->getOpt('videoGuid') ?? null;
        $height = $this->getOpt('height') ?? null;
        $width = $this->getOpt('width') ?? null;
        $state = $this->getOpt('state') ?? 'ready';

        if (!$videoGuid || !$height || !$width) {
            $this->out('Missing required params - calls must include videoGuid, height and width');
            return;
        }

        $this->cloudflareStreamsWebhooks->onWebhook(
            request: (new ServerRequest())
                ->withParsedBody([
                    'status' => ['state' => $state ],
                    'meta' => [ 'guid' => $videoGuid ],
                    'input' => [
                        'width' => $width,
                        'height' => $height
                    ]
                ]),
            bypassAuthentication: true
        );
    }

    /**
     * Converts a video entity to a livestream, by given linked activity guid.
     * @param string activityGuid - guid of the activity.
     * @param string streamId - id of the stream from Cloudflare Streams.
     * @example
     *  - php cli.php Cloudflare convertVideoToLivestream --activityGuid='~activity_guid~' --streamId='~stream_id~'
     * @return void
     */
    public function convertVideoToLivestream(): void
    {
        $activityGuid = $this->getOpt('activityGuid') ?? false;
        $streamId = $this->getOpt('streamId') ?? false;

        $activity = $this->entitiesBuilder->single($activityGuid);

        if (!$streamId) {
            $this->out('No stream id provided');
            return;
        }

        if (!$activity || !($activity instanceof Activity) || !$activity->hasAttachments()) {
            $this->out("[Cloudflare CLI] No Activity with attachments was found with the guid: '$activityGuid'");
            return;
        }

        $videoGuid = $activity->attachments[0]['guid'];
        $video = $this->entitiesBuilder->single($videoGuid);

        if (!$video || !($video instanceof Video)) {
            $this->out("[Cloudflare CLI] No Video entity was found linked to the Activity with the guid: '$activityGuid'");
            return;
        }

        // override ACL so that CLI can update entities.
        $ia = $this->acl->setIgnore(true);

        // update activity to patch a true value for "livestream" into the attachments.
        $firstAttachment = $activity->attachments[0];
        $firstAttachment['livestream'] = true;
        $activity->attachments = [$firstAttachment];
        (new Save())->setEntity($activity)->save();

        // update the cloudflare id of the video to that of the stream id and set transcoded status to completed.
        $video->setCloudflareId($streamId);
        $video->setTranscodingStatus(TranscodeStates::COMPLETED);
        (new Save())->setEntity($video)->save();

        // set ACL state back once done saving.
        $this->acl->setIgnore($ia);

        $this->out('Done');
    }
}
