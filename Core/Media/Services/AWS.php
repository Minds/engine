<?php
/**
 * Minds Archive AWS Service
 */

namespace Minds\Core\Media\Services;

use Aws\ElasticTranscoder\ElasticTranscoderClient;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Minds\Core\Config;
use Minds\Core\Di\Di;

class AWS implements ServiceInterface
{
    private $s3;
    private $et;

    private $key;
    private $dir = 'cinemr_data';

    public function __construct($custom = [])
    {
        $awsConfig = Di::_()->get('Config')->get('aws') ?? [];
        $opts = [
            'region' => $awsConfig['region'] ?? 'us-east-1',
        ];

        if (!isset($awsConfig['useRoles']) || !$awsConfig['useRoles']) {
            $opts['credentials'] = [
                'key' => $awsConfig['key'] ?? null,
                'secret' => $awsConfig['secret'] ?? null,
            ];
        }

        $s3Opts = $opts;
        $etOpts = $opts;

        if (isset($custom['s3'])) {
            $s3Opts = array_merge($opts, $custom['s3']);
        }

        if (isset($custom['et'])) {
            $etOpts = array_merge($opts, $custom['et']);
        }

        $this->s3 = new S3Client(array_merge([ 'version' => '2006-03-01' ], $s3Opts));
        $this->et = new ElasticTranscoderClient(array_merge([ 'version' => '2012-09-25' ], $etOpts));

        $this->dir = $awsConfig['elastic_transcoder']['dir'];
    }

    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    public function saveToFilestore($file)
    {
        try {
            if (is_string($file)) {
                $result =  $this->s3->putObject([
                  'ACL' => 'public-read',
                  'Bucket' => 'cinemr',
                  'Key' => "$this->dir/$this->key/source",
                  //'ContentLength' => $_SERVER['CONTENT_LENGTH'],
                  //'ContentLength' => filesize($file),
                  'Body' => fopen($file, 'r'),
                  ]);
                return $this;
            } elseif (is_resource($file)) {
                $result =  $this->s3->putObject([
                  'ACL' => 'public-read',
                  'Bucket' => 'cinemr',
                  'Key' => "$this->dir/$this->key/source",
                  'ContentLength' => $_SERVER['CONTENT_LENGTH'],
                  'Body' => $file
                ]);
                return $this;
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
        throw new \Exception('Sorry, only strings and stream resource are accepted');
    }

    public function getTorrent($file)
    {
        $objectTorrent = $this->s3->getObjectTorrent([
            'Bucket' => 'cinemr',
            'Key' => "{$this->dir}/{$this->key}/$file"
        ]);

        /** @var Stream $body */
        $body = $objectTorrent->get('Body');

        return $body->getContents();
    }
}
