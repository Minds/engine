<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\MultiTenant\Bootstrap\Clients\GoogleFaviconClient;
use Minds\Core\MultiTenant\Bootstrap\Clients\JinaClient;
use Minds\Core\MultiTenant\Bootstrap\Services\MultiTenantBootstrapService;
use OpenAI\Client as OpenAIClient;
use OpenAI;
use GuzzleHttp\Client as GuzzleClient;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Bootstrap\Clients\ScreenshotOneClient;
use Minds\Core\MultiTenant\Bootstrap\Delegates\ActivityCreationDelegate;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateConfigDelegate;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateLogosDelegate;
use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\ConfigGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\ContentGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\LogoGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\WebsiteIconExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MetadataExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ScreenshotExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MarkdownExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ContentExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\HorizontalLogoExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MobileSplashLogoExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ThemeExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Processors\LogoImageProcessor;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Helpers\Image as ImageHelpers;

class ServicesProvider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            MarkdownExtractor::class,
            function (Di $di): MarkdownExtractor {
                return new MarkdownExtractor(
                    jinaClient: $di->get(JinaClient::class),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            ScreenshotExtractor::class,
            function (Di $di): ScreenshotExtractor {
                return new ScreenshotExtractor(
                    screenshotOneClient: $di->get(ScreenshotOneClient::class),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            MetadataExtractor::class,
            function (Di $di): MetadataExtractor {
                return new MetadataExtractor(
                    metascraperService: $di->get('Metascraper\Service'),
                    guzzleClient: new GuzzleClient(),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            WebsiteIconExtractor::class,
            function (Di $di): WebsiteIconExtractor {
                return new WebsiteIconExtractor(
                    googleFaviconClient: $di->get(GoogleFaviconClient::class),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            ThemeExtractor::class,
            function (Di $di): ThemeExtractor {
                return new ThemeExtractor(
                    openAiClient: $di->get(OpenAIClient::class)
                );
            }
        );

        $this->di->bind(
            ContentExtractor::class,
            function (Di $di): ContentExtractor {
                return new ContentExtractor(
                    openAiClient: $di->get(OpenAIClient::class)
                );
            }
        );

        $this->di->bind(
            ConfigGenerationHandler::class,
            function (Di $di): ConfigGenerationHandler {
                return new ConfigGenerationHandler(
                    themeExtractor: $di->get(ThemeExtractor::class),
                    updateConfigDelegate: $di->get(UpdateConfigDelegate::class),
                    progressRepository: $di->get(BootstrapProgressRepository::class),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            ContentGenerationHandler::class,
            function (Di $di): ContentGenerationHandler {
                return new ContentGenerationHandler(
                    contentExtractor: $di->get(ContentExtractor::class),
                    activityCreationDelegate: $di->get(ActivityCreationDelegate::class),
                    progressRepository: $di->get(BootstrapProgressRepository::class),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            LogoGenerationHandler::class,
            function (Di $di): LogoGenerationHandler {
                return new LogoGenerationHandler(
                    metadataExtractor: $di->get(MetadataExtractor::class),
                    websiteIconExtractor: $di->get(WebsiteIconExtractor::class),
                    horizontalLogoExtractor: $di->get(HorizontalLogoExtractor::class),
                    mobileSplashLogoExtractor: $di->get(MobileSplashLogoExtractor::class),
                    updateLogosDelegate: $di->get(UpdateLogosDelegate::class),
                    progressRepository: $di->get(BootstrapProgressRepository::class),
                    imageHelpers: new ImageHelpers(),
                    logger: $di->get('Logger'),
                );
            }
        );

        $this->di->bind(
            LogoImageProcessor::class,
            function (Di $di): LogoImageProcessor {
                return new LogoImageProcessor();
            }
        );

        $this->di->bind(
            MobileSplashLogoExtractor::class,
            function (Di $di): MobileSplashLogoExtractor {
                return new MobileSplashLogoExtractor(
                    logoImageProcessor: $di->get(LogoImageProcessor::class),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            HorizontalLogoExtractor::class,
            function (Di $di): HorizontalLogoExtractor {
                return new HorizontalLogoExtractor(
                    logoImageProcessor: $di->get(LogoImageProcessor::class),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            BootstrapProgressService::class,
            function (Di $di): BootstrapProgressService {
                return new BootstrapProgressService(
                    progressRepository: $di->get(BootstrapProgressRepository::class),
                    logger: $di->get('Logger')
                );
            }
        );

        $this->di->bind(
            MultiTenantBootstrapService::class,
            function (Di $di): MultiTenantBootstrapService {
                return new MultiTenantBootstrapService(
                    multiTenantBootService: $di->get(MultiTenantBootService::class),
                    progressRepository: $di->get(BootstrapProgressRepository::class),
                    markdownExtractor: $di->get(MarkdownExtractor::class),
                    screenshotExtractor: $di->get(ScreenshotExtractor::class),
                    metadataExtractor: $di->get(MetadataExtractor::class),
                    logoGenerationHandler: $di->get(LogoGenerationHandler::class),
                    configGenerationHandler: $di->get(ConfigGenerationHandler::class),
                    contentGenerationHandler: $di->get(ContentGenerationHandler::class),
                    entitiesBuilder: $di->get(EntitiesBuilder::class),
                    logger: $di->get('Logger')
                );
            }
        );
    }
}
