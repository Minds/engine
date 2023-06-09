<?php
/**
 * Minds FFMpeg. (This now deprecated in favour of Core/Media/Video/Transcoder/Manager)
 */

namespace Minds\Core\Media\Services;

use GuzzleHttp\Client as HttpClient;
use Aws\S3\S3Client;
use FFMpeg\FFMpeg as FFMpegClient;
use FFMpeg\FFProbe as FFProbeClient;
use FFMpeg\Filters\Video\ResizeFilter;
use Minds\Core;
use Minds\Core\Config;
use Minds\Entities\Video;
use Minds\Core\Di\Di;
use Minds\Core\Media\TranscodingStatus;

class FFMpeg implements ServiceInterface
{
    /** @var Queue $queue */
    private $queue;

    /** @var FFMpeg $ffmpeg */
    private $ffmpeg;

    /** @var FFProbe */
    private $ffprobe;

    /** @var Config $config */
    private $config;

    /** @var S3Client $s3 */
    private $s3;

    /** @var string $key */
    private $key;

    /** @var string $dir */
    private $dir = 'cinemr_data';

    /** @var bool $full_hd */
    private $full_hd = false;

    public function __construct(
        $queue = null,
        $ffmpeg = null,
        $ffprobe = null,
        $s3 = null,
        $config = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->queue = $queue ?: Core\Queue\Client::build();
        $this->ffmpeg = $ffmpeg ?: FFMpegClient::create([
            'ffmpeg.binaries' => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'ffmpeg.threads' => $this->config->get('transcoder')['threads'] ?? 1,
            'timeout' => 0,
        ]);
        $this->ffprobe = $ffprobe ?: FFProbeClient::create([
            'ffprobe.binaries' => '/usr/bin/ffprobe',
        ]);

        // AWS client
        $awsConfig = $this->config->get('aws');
        $opts = ['region' => $awsConfig['region'] ?? 'us-east-1'];

        if (!isset($awsConfig['useRoles']) || !$awsConfig['useRoles']) {
            $opts['credentials'] = [
                'key' => $awsConfig['key'] ?? null,
                'secret' => $awsConfig['secret'] ?? null,
            ];
        }

        // OSS client (S3 compat)
        $ociConfig = $this->config->get('oci')['oss_s3_client'];
        $ociOpts = [
            'region' => $ociConfig['region'] ?? 'us-east-1', // us-east-1 defaults to current OCI region
            'endpoint' => $ociConfig['endpoint'],
            'use_path_style_endpoint' => true, // Required for OSS
            'credentials' => [
                'key' => $ociConfig['key'] ?? null,
                'secret' => $ociConfig['secret'] ?? null,
            ]
        ];

        // Set primary and secondary clients
        $primaryOpts = $this->config->get('transcoder')['use_oracle_oss'] ? $ociOpts : $opts;
        $secondaryOpts = $this->config->get('transcoder')['use_oracle_oss'] ? $opts : $ociOpts;

        $this->s3 = $s3 ?: new S3Client(array_merge(['version' => '2006-03-01'], $primaryOpts));
        $this->secondaryS3 = $s3 ?: new S3Client(array_merge(['version' => '2006-03-01'], $secondaryOpts));

        $this->dir = $this->config->get('transcoder')['dir'] ?? '';
    }

    /**
     * @param $key
     * @return FFMpeg
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @param bool $value
     * @return FFMpeg
     */
    public function setFullHD(bool $value)
    {
        $this->full_hd = $value;
        return $this;
    }

    /**
     * Create a PresignedUrl for client based uploads
     * @param string $key
     * @return string
     */
    private function getOciPresignedUrl(string $key): string
    {
        $oci_api_config = [
            'tenantId' => $this->config->get('oci')['api_auth']['tenant_id'],
            'userId' => $this->config->get('oci')['api_auth']['user_id'],
            'keyFingerprint' => $this->config->get('oci')['api_auth']['key_fingerprint'],
            'privateKey' => $this->config->get('oci')['api_auth']['private_key'],
            'region' => 'us-ashburn-1',
            'service' => 'objectstorage',
            'bucketName' => 'cinemr',
            'bucketNamespace' => $this->config->get('oci')['api_auth']['bucket_namespace']
        ];

        $privateKey = base64_decode($this->config->get('oci')['api_auth']['private_key'], true);
        if (!$privateKey) {
            throw new \Exception('Unable to load private key');
        }

        // Create Guzzle client
        $client = new Client(['base_uri' => "https://objectstorage.{$oci_api_config['region']}.oraclecloud.com"]);

        // Create pre-authenticated request
        $data = [
            'name' => $key,
            'objectName' => $key,
            'accessType' => 'ObjectWrite',
            'timeExpires' => gmdate('Y-m-d\TH:i:s\Z', strtotime('+20 minutes')),
        ];
        $headers = [
            'Content-Type' => 'application/json',
            'x-content-sha256' => base64_encode(hash('sha256', json_encode($data), true)),
            'date' => gmdate('D, d M Y G:i:s T'),
        ];
        $requestTarget = "(request-target): post /n/{$oci_api_config['bucketNamespace']}/b/{$oci_api_config['bucketName']}/p/";

        // Create the signing string
        $signingString = implode("\n", [
            $requestTarget,
            'date: ' . $headers['date'],
            'content-type: ' . $headers['Content-Type'],
            'x-content-sha256: ' . $headers['x-content-sha256'],
        ]);

        // Create the signature
        openssl_sign($signingString, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        // Add Authorization header
        $apiKey = $oci_api_config['tenantId'] . '/' . $oci_api_config['userId'] . '/' . $oci_api_config['keyFingerprint'];
        $headers['Authorization'] = 'Signature version="1",headers="(request-target) date content-type x-content-sha256",keyId="' . $apiKey . '",algorithm="rsa-sha256",signature="' . $signature . '"';

        try {
            $response = $client->request('POST', "/n/{$oci_api_config['bucketNamespace']}/b/{$oci_api_config['bucketName']}/p/", [
                'headers' => $headers,
                'body' => json_encode($data),
            ]);
            $result = json_decode($response->getBody(), true);
            error_log("Pre-authenticated request created with name {$result['name']}\n");
            error_log("Upload URL: {$result['accessUri']}\n");

            return $result['accessUri'];
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Create a PresignedUr for client based uploads
     * @return string
     */
    public function getPresignedUrl()
    {
        if ($this->config->get('transcoder')['use_oracle_oss']) {
            error_log("Using OCI Presigned URL\n");
            $signedUrl = $this->getOciPresignedUrl("$this->dir/$this->key/source");
        } else {
            error_log("Using AWS Presigned URL\n");
            $cmd = $this->s3->getCommand('PutObject', [
                'Bucket' => 'cinemr',
                'Key' => "$this->dir/$this->key/source",
            ]);

            $signedUrl = $this->s3->createPresignedRequest($cmd, '+20 minutes')->getUri();
        }

        return (string) $signedUrl;
    }

    public function saveToFilestore($file)
    {
        try {
            if (is_string($file)) {
                $result = $this->s3->putObject([
                  'ACL' => 'public-read',
                  'Bucket' => 'cinemr',
                  'Key' => "$this->dir/$this->key/source",
                  //'ContentLength' => $_SERVER['CONTENT_LENGTH'],
                  //'ContentLength' => filesize($file),
                  'Body' => fopen($file, 'r'),
                  ]);

                return $this;
            } elseif (is_resource($file)) {
                $result = $this->s3->putObject([
                  'ACL' => 'public-read',
                  'Bucket' => 'cinemr',
                  'Key' => "$this->dir/$this->key/source",
                  'ContentLength' => $_SERVER['CONTENT_LENGTH'],
                  'Body' => $file,
                ]);

                return $this;
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
        throw new \Exception('Sorry, only strings and stream resource are accepted');
    }

    /**
     * Queue the video to be transcoded.
     *
     * @return $this
     */
    public function transcode()
    {
        //queue for transcoding
        $this->queue
            ->setQueue('Transcode')
            ->send([
                'key' => $this->key,
                'full_hd' => $this->full_hd,
            ]);

        return $this;
    }

    /**
     * Called when the queue is running.
     */
    public function onQueue()
    {
        $sourcePath = tempnam(sys_get_temp_dir(), $this->key);

        try {
            //download the file from s3
            $this->s3->getObject([
                'Bucket' => 'cinemr',
                'Key' => "$this->dir/$this->key/source",
                'SaveAs' => $sourcePath,
            ]);
        } catch (Aws\S3\Exception\S3Exception $e) {
            if ($e->getAwsErrorCode() === 'NoSuchKey') {
                $this->secondaryS3->getObject([
                    'Bucket' => 'cinemr',
                    'Key' => "$this->dir/$this->key/source",
                    'SaveAs' => $sourcePath,
                ]);
            } else {
                throw $e;
            }
        }

        $video = $this->ffmpeg->open($sourcePath);

        $tags = null;

        try {
            $videostream = $this->ffprobe
                ->streams($sourcePath)
                ->videos()
                ->first();

            // get video metadata
            $tags = $videostream->get('tags');
        } catch (\Exception $e) {
            error_log('Error getting videostream information');
        }

        try {
            $thumbnailsDir = $sourcePath.'-thumbnails';
            @mkdir($thumbnailsDir, 0600, true);

            //create thumbnails
            $length = round((int) $this->ffprobe->format($sourcePath)->get('duration'));
            $secs = [0, 1, round($length / 2), $length - 1, $length];
            foreach ($secs as $sec) {
                $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($sec));
                $pad = str_pad($sec, 5, '0', STR_PAD_LEFT);
                $path = $thumbnailsDir.'/'."thumbnail-$pad.png";
                $frame->save($path);
                @$this->uploadTranscodedFile($path, "thumbnail-$pad.png");
                //cleanup uploaded file
                @unlink($path);
            }

            //cleanup thumbnails director
            @unlink($thumbnailsDir);
        } catch (\Exception $e) {
        }

        $rotated = isset($tags['rotate']) && in_array($tags['rotate'], [270, 90], true);

        $outputs = [];
        $presets = $this->config->get('transcoder')['presets'];
        foreach ($presets as $prefix => $opts) {
            $opts = array_merge([
                'bitrate' => null,
                'audio_bitrate' => null,
                'prefix' => null,
                'width' => '720',
                'height' => '480',
                'formats' => ['mp4', 'webm'],
            ], $opts);

            if ($opts['pro'] && !$this->full_hd) {
                continue;
            }

            if ($rotated && isset($videostream)) {
                $ratio = $videostream->get('width') / $videostream->get('height');
                $width = round($opts['height'] * $ratio);
                $opts['width'] = $opts['height'];
                $opts['height'] = $width;
            }

            $video->filters()
                ->resize(
                    new \FFMpeg\Coordinate\Dimension($opts['width'], $opts['height']),
                    $rotated ? ResizeFilter::RESIZEMODE_FIT : ResizeFilter::RESIZEMODE_SCALE_WIDTH
                )
                ->synchronize();

            $formatMap = [
                'mp4' => (new \FFMpeg\Format\Video\X264())
                    ->setAudioCodec('aac'),
                'webm' => new \FFMpeg\Format\Video\WebM(),
            ];

            foreach ($opts['formats'] as $format) {
                $pfx = ($rotated ? $opts['width'] : $opts['height']).'.'.$format;
                $path = $sourcePath.'-'.$pfx;
                try {
                    echo "\nTranscoding: $path ($this->key)\n";

                    $formatMap[$format]->on('progress', function ($a, $b, $pct) {
                        echo "\r$pct% transcoded";
                        // also emit out to cassandra so frontend can keep track
                    });

                    $formatMap[$format]
                        ->setKiloBitRate($opts['bitrate'])
                        // ->setAudioChannels(2)
                        ->setAudioKiloBitrate($opts['audio_bitrate']);
                    $video->save($formatMap[$format], $path);

                    //now upload to s3
                    $this->uploadTranscodedFile($path, $pfx);
                } catch (\Exception $e) {
                    echo " failed {$e->getMessage()}";
                    //cleanup tmp file
                    @unlink($path);
                }
            }
        }

        //cleanup original file
        @unlink($sourcePath);

        return $this;
    }

    protected function uploadTranscodedFile($path, $prefix)
    {
        return $this->s3->putObject([
            'ACL' => 'public-read',
            'Bucket' => 'cinemr',
            'Key' => "$this->dir/$this->key/$prefix",
            //'ContentLength' => $_SERVER['CONTENT_LENGTH'],
            //'ContentLength' => filesize($file),
            'Body' => fopen($path, 'r'),
        ]);
    }

    /**
     * @param Video $entity
     *
     * Queries S3 in the cinemr bucket for all keys matching dir/guid
     * Uses the AWS/Result object to construct a transcodingStatus based on the content of the key
     */
    public function verify(Video $video)
    {
        $awsResult = $this->s3->listObjects([
            'Bucket' => 'cinemr',
            'Prefix' => "{$this->dir}/{$video->guid}",
        ]);
        $status = new TranscodingStatus($video, $awsResult);

        return $status;
    }
}
