<?php
/**
 * Transcoder manager
 */
namespace Minds\Core\Media\Video\Transcoder;

use Minds\Core\Media\Video\Transcode\Delegates\QueueDelegate;
use Minds\Entities\Video;
use Minds\Traits\MagicAttributes;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Cassandra\Bigint;
use Cassandra\Timestamp;

class Repository
{
    /** @var Client */
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Return a list of transcodes
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts): Response
    {
        $opts = array_merge([
            'guid' => null,
            'profileId' => null,
            'status' => null,
        ], $opts);

        $statement = "SELECT 
            guid,
            profile_id,
            progress,
            status,
            last_event_timestamp_ms,
            bytes
            FROM video_transcodes";

        $where = [];
        $values = [];

        if ($opts['guid']) {
            $where[] = "guid = ?";
            $values[] = new Bigint($opts['guid']);
        }

        if ($opts['profileId']) {
            $where[] = "profile_id = ?";
            $values[] = $opts['profileId'];
        }

        if ($opts['status']) {
            $where[] = "status = ?";
            $values[] = $opts['status'];
        }

        $statement .= " WHERE " . implode(' AND ', $where);

        $prepared = new Custom();
        $prepared->query($statement, $values);

        try {
            $result = $this->db->request($prepared);
        } catch (\Exception $e) {
            return new Response(); // TODO: make sure error is attached to response
        }

        $response = new Response;

        foreach ($result as $row) {
            $response[] = $this->buildTranscodeFromRow($row);
        }

        return $response;
    }

    /**
     * Return a single transcode
     * @param string $urn
     * @return Transcode
     */
    public function get(string $urn): ?Transcode
    {
        $urn = Urn::_($urn);
        list($guid, $profile) = explode('-', $urn->getNss());

        $statement = "SELECT 
            guid,
            profile_id,
            progress,
            status,
            last_event_timestamp_ms,
            bytes
            FROM video_transcodes
            WHERE guid = ?
            AND profile = ?";
        $values = [
            new Bigint($guid),
            $profile
        ];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        try {
            $result = $this->db->request($prepared);
        } catch (\Exception $e) {
            return null;
        }

        $row = $result[0];

        $transcode = $this->buildTranscodeFromRow($row);

        return $transcode;
    }

    /**
     * Add a transcode to the database
     * @param Transcode $transcode
     * @return bool
     */
    public function add(Transcode $transcode): bool
    {
        $statement = "INSERT INTO video_transcodes (guid, profile_id, status) VALUES (?, ?, ?)";
        $values = [
            new Bigint($transcode->getGuid()),
            $transcode->getProfile()->getId(),
            $transcode->getStatus(),
        ];

        $prepared = new Custom();
        $prepared->query($statement, $values);

        try {
            $this->db->request($prepared);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Update the transcode
     * @param Transcode $transcode
     * @param array $dirty - list of fields that have changed
     * @return bool
     */
    public function update(Transcode $transcode, array $dirty = []): bool
    {
        // Always update lastEventTimestampMs
        $transcode->setLastEventTimestampMs(round(microtime(true) * 1000));
        $dirty[] = 'lastEventTimestampMs';

        $statement = "UPDATE video_transcodes";

        $set = [];

        foreach ($dirty as $field) {
            switch ($field) {
                case 'progress':
                    $set['progress'] = (int) $transcode->getProgress(); // This is a percentage basedf off 100
                    break;
                case 'status':
                    $set['status'] = (string) $transcode->getStatus();
                    break;
                case 'lastEventTimestampMs':
                    $set['last_event_timestamp_ms'] = new Timestamp($transcode->getLastEventTimestampMs() / 1000);
                    break;
                case 'lengthSecs':
                    $set['length_secs'] = (int) $transcode->getLengthSecs();
                    break;
                case 'bytes':
                    $set['bytes'] = (int) $transcode->getBytes();
                    break;
                case 'failureReason':
                    $set['failure_reason'] = $transcode->getFailureReason();
                    break;
            }
        }

        // Convert our $set to statement
        $statement .= " SET " . implode(' , ', array_map(function ($field) {
            return "$field = ?";
        }, array_keys($set)));

        // Move to values array
        $values = array_values($set);

        // Say what we are updating
        $statement .= " WHERE guid = ? AND profile_id = ?";
        $values[] = new Bigint($transcode->getGuid());
        $values[] = (string) $transcode->getProfile()->getId();

        // Prepared statement
        $prepared = new Custom();
        $prepared->query($statement, $values);

        try {
            $this->db->request($prepared);
        } catch (\Exception $e) {
            return false;
        }

        // also update the status in the video
        try {
            if ($transcode->getStatus() !== $transcode->getVideo()->getTranscodingStatus()) {
                $transcode->getVideo()
                    ->patch([
                        'transcoding_status' => $transcode->getStatus(),
                    ])
                    ->save();
            }
        } catch (\Exception $e) {
            error_log('[Transcoder\Repository] ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Delete a transcode
     * @param Transcode $transcode
     * @return bool
     */
    public function delete(Transcode $transcode): bool
    {
        $statement = "DELETE FROM video_transcodes WHERE guid = ? and profile_id = ?";
        $values = [
            new Bigint($transcode->getGuid()),
            (string) $transcode->getProfile()->getId(),
        ];

        // Prepared statement
        $prepared = new Custom();
        $prepared->query($statement, $values);

        try {
            $this->db->request($prepared);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Build transcode from an array of data
     * @param array $row
     * @return Transcode
     */
    protected function buildTranscodeFromRow(array $row): Transcode
    {
        $transcode = new Transcode();
        $transcode->setGuid((string) $row['guid'])
            ->setProfile(TranscodeProfiles\Factory::build((string) $row['profile_id']))
            ->setProgress($row['progress'])
            ->setStatus($row['status'])
            ->setLastEventTimestampMs($row['last_event_timestamp_ms'] ? round($row['last_event_timestamp_ms']->microtime(true) * 1000) : null)
            ->setLengthSecs($row['length_secs'])
            ->setBytes($row['bytes']);
        return $transcode;
    }
}
