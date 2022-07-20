<?php

namespace Minds\Core\SEO\Sitemaps;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Spatie\Sitemap\SitemapGenerator;
use Aws\S3\S3Client;

class Manager
{
    /** @var Config */
    protected $config;

    /** @var SitemapGenerator */
    protected $generator;

    /** @var S3 */
    protected $s3;

    /** @var string */
    protected $tmpOutputDirPath;

    protected $resolvers = [
        Resolvers\MarketingResolver::class,
        Resolvers\DiscoveryResolver::class,
        Resolvers\ActivityResolver::class,
        Resolvers\UsersResolver::class,
        Resolvers\BlogsResolver::class,
    ];

    public function __construct($config = null, $generator = null, $s3 = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
        $this->tmpOutputDirPath = $this->getOutputDir();
        $this->generator = $generator ?: new \Icamys\SitemapGenerator\SitemapGenerator(substr($this->config->get('site_url'), 0, -1), $this->tmpOutputDirPath);
        $this->s3 = $s3 ?? new S3Client([ 'version' => '2006-03-01', 'region' => 'us-east-1' ]);
    }

    /**
     * Set the resolvers to user
     * @param array $resolvers
     * @return self
     */
    public function setResolvers(array $resolvers): self
    {
        $this->resolvers = $resolvers;
        return $this;
    }

    /**
     * Build the sitemap
     * @return void
     */
    public function build(): void
    {
        $this->generator->setSitemapFileName("sitemaps/sitemap.xml");
        $this->generator->setSitemapIndexFileName("sitemaps/sitemap.xml");
        $this->generator->setMaxURLsPerSitemap(50000);

        foreach ($this->resolvers as $resolver) {
            $resolver = is_object($resolver) ? $resolver : new $resolver;
            foreach ($resolver->getUrls() as $sitemapUrl) {
                $this->generator->addURL(
                    $sitemapUrl->getLoc(),
                    $sitemapUrl->getLastModified(),
                    $sitemapUrl->getChangeFreq(),
                    $sitemapUrl->getPriority()
                );
            }
        }

        $this->generator->flush();
        $this->generator->finalize();

        // Upload to s3
        $this->uploadToS3();

        $this->generator->submitSitemap();
    }

    /**
     * Get and make an output directory
     * @return string
     */
    protected function getOutputDir(): string
    {
        $outputDir = sys_get_temp_dir() . '/' . uniqid();
        mkdir($outputDir . '/sitemaps', 0700, true);
        return $outputDir;
    }

    /**
     * Uploads to S3
     * @return void
     */
    protected function uploadToS3(): void
    {
        $dir = $this->tmpOutputDirPath . '/sitemaps/';
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $this->s3->putObject([
                  'ACL' => 'public-read',
                  'Bucket' => 'minds-sitemaps',
                  'Key' => "minds.com/$file",
                  'Body' => fopen($dir.$file, 'r'),
              ]);
            unlink($dir.$file);
        }
        rmdir($dir);
    }
}
