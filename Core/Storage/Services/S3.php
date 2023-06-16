<?php

namespace Minds\Core\Storage\Services;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Media\Services\AwsS3Client;
use Minds\Core\Media\Services\OciS3Client;
use Minds\Helpers\File;

class S3 implements ServiceInterface
{
    /** @var S3Client */
    public $s3;
    public $filepath;
    public $mode;

    private $modes = [
      'read',
      'write'
    ];

    public function __construct(
        protected ?S3Client $awsS3Client = null,
        protected ?S3Client $ociS3Client = null,
        protected ?Config $config = null
    ) {
        $this->awsS3Client ??= Di::_()->get(AwsS3Client::class);
        $this->ociS3Client ??= Di::_()->get(OciS3Client::class);
        $this->config ??= Di::_()->get('Config');
    }

    public function open($path, $mode)
    {
        if ($mode && !in_array($mode, $this->modes, true)) {
            throw new \Exception("$mode is not a supported type");
        }

        $this->mode = $mode;

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

        $useOci = $this->config->get('storage')['oci_primary'] ?? false;

        $s3 = $useOci ? $this->ociS3Client : $this->awsS3Client;

        $bucketName = $useOci ? $this->config->get('storage')['oci_bucket_name'] : $this->config->get('aws')['bucket'];

        $write =  $s3->putObject([
          // 'ACL' => 'public-read',
          'Bucket' => $bucketName,
          'Key' => $this->filepath,
          'ContentType' => $mimeType,
          'ContentLength' => strlen($data),
          'Body' => $data,
        ]);

        return !!$write;
    }

    public function read($length = 0)
    {
        switch ($this->mode) {
            case "read":
            default:
                try { // to read object from OCI OSS bucket
                    $result = $this->ociS3Client->getObject([
                        'Bucket' => $this->config->get('storage')['oci_bucket_name'],
                        'Key' => $this->filepath
                    ]);
                    return $result['Body'];
                } catch (S3Exception $e) {
                    try { // to read object from AWS S3 bucket on failure
                        $result = $this->awsS3Client->getObject([
                            'Bucket' => $this->config->get('aws')['bucket'],
                            'Key' => $this->filepath
                        ]);
                        return $result['Body'];
                    } catch (Exception $e) {
                        return "";
                    }
                }
                break;
        }
    }

    public function seek($offset = 0)
    {
        //not supported
    }

    public function destroy()
    {
    }
}
