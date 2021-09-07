<?php

namespace Minds\Core\Storage\Services;

use Aws\S3\S3Client;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Helpers\File;

class S3 implements ServiceInterface
{
    /** @var S3Client */
    public $s3;
    public $filepath;
    public $mode;

    private $modes = [
      'read',
      'read-uri',
      'redirect',
      'write'
    ];

    public function open($path, $mode)
    {
        if ($mode && !in_array($mode, $this->modes, true)) {
            throw new \Exception("$mode is not a supported type");
        }

        $this->mode = $mode;

        $awsConfig = Di::_()->get('Config')->get('aws');
        $opts = [
            'region' => 'us-east-1',
            'version' => 'latest',
            'http' => [
                'connect_timeout' => 1, //if we don't connect in 1 second
                'timeout' => 120 //if the request takes longer than 2 minutes (120 seconds)
            ],
            'use_accelerate_endpoint' => true,
        ];

        if (!isset($awsConfig['useRoles']) || !$awsConfig['useRoles']) {
            $opts['credentials'] = [
                'key' => $awsConfig['key'],
                'secret' => $awsConfig['secret'],
            ];
        }
        
        $this->s3 = new S3Client($opts);

        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
            $path = str_replace('//', '/', $path);
        }

        $this->filepath = $path;
        return $this;
    }

    public function close()
    {
    }

    public function write($data)
    {
        $mimeType = File::getMimeType($data);

        $write =  $this->s3->putObject([
          // 'ACL' => 'public-read',
          'Bucket' => Config::_()->aws['bucket'],
          'Key' => $this->filepath,
          'ContentType' => $mimeType,
          'ContentLength' => strlen($data),
          'Body' => $data,
        ]);

        return true;
    }

    public function read($length = 0)
    {
        switch ($this->mode) {
            case "read-uri":
                $url = $this->s3->getObjectUrl(Config::_()->aws['bucket'], $this->filepath, "+15 minutes");
                return $url;
                break;
            case "read":
                try {
                    $result = $this->s3->getObject([
                        'Bucket' => Config::_()->aws['bucket'],
                        'Key' => $this->filepath
                    ]);
                    return $result['Body'];
                } catch (\Exception $e) {
                    return "";
                }
                break;
            case "redirect":
            default:
                $url = $this->s3->getObjectUrl(Config::_()->aws['bucket'], $this->filepath, "+15 minutes");
                header("Location: $url");
                exit;
        }
    }

    /**
     * Return a signed url
     * @return string
     */
    public function getSignedUrl(): string
    {
        $cmd = $this->s3->getCommand('GetObject', [
           'Bucket' => Config::_()->aws['bucket'],
           'Key' => $this->filepath,
        ]);
        $request = $this->s3->createPresignedRequest($cmd, '+20 minutes');
        return (string) $request->getUri();
    }

    public function seek($offset = 0)
    {
        //not supported
    }

    public function destroy()
    {
    }
}
