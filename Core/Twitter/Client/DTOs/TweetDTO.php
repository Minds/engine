<?php

declare(strict_types=1);

namespace Minds\Core\Twitter\Client\DTOs;

use JsonSerializable;
use Minds\Entities\ExportableInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setText(string $text)
 * @method string getText()
 * @method self setReplySettings(string $replySettings)
 * @method string|null getReplySettings()
 * @method self setReply(Reply $reply)
 * @method Reply|null getReply()
 * @method self setQuoteTweetId(string $quoteTweetId)
 * @method string|null getQuoteTweetId()
 * @method self setPoll(Poll $poll)
 * @method Poll|null getPoll()
 * @method self setMedia(Media $media)
 * @method Media|null getMedia()
 * @method self setGeo(Geo $geo)
 * @method Geo|null getGeo()
 * @method self setForSuperFollowersOnly(bool $forSuperFollowersOnly)
 * @method bool getForSuperFollowersOnly()
 * @method self setDirectMessageDeepLink(string $directMessageDeepLink)
 * @method string|null getDirectMessageDeepLink()
 */
class TweetDTO implements JsonSerializable, ExportableInterface
{
    use MagicAttributes;

    private string $text;
    private ?string $replySettings = null;
    private ?Reply $reply = null;
    private ?string $quoteTweetId = null;
    private ?Poll $poll = null;
    private ?Media $media = null;
    private ?Geo $geo = null;
    private bool $forSuperFollowersOnly = false;
    private ?string $directMessageDeepLink = null;

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): array
    {
        $data = [
            'text' => $this->getText(),
        ];

        if ($this->getGeo()) {
            $data['geo'] = $this->getGeo()?->export();
        }

        if ($this->getMedia()) {
            $data['media'] = $this->getMedia()?->export();
        }

        if ($this->getPoll()) {
            $data['poll'] = $this->getPoll()?->export();
        }

        if ($this->getReply()) {
            $data['reply'] = $this->getReply()?->export();
        }

        if ($this->getReplySettings()) {
            $data['reply_settings'] = $this->getReplySettings();
        }

        if ($this->getDirectMessageDeepLink()) {
            $data['direct_message_deep_link'] = $this->getDirectMessageDeepLink();
        }

        if ($this->getForSuperFollowersOnly()) {
            $data['for_super_followers_only'] = $this->getForSuperFollowersOnly();
        }

        return $data;
    }

    public function export(array $extras = []): array
    {
        return [
            'direct_message_deep_link' => $this->getDirectMessageDeepLink(),
            'for_super_followers_only' => $this->getForSuperFollowersOnly(),
            'geo' => $this->getGeo()?->export() ?? null,
            'media' => $this->getMedia()?->export() ?? null,
            'poll' => $this->getPoll()?->export() ?? null,
            'reply' => $this->getReply()?->export() ?? null,
            'reply_settings' => $this->getReplySettings(),
            'text' => $this->getText(),
        ];
    }
}
