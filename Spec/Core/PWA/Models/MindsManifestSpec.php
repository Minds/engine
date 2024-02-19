<?php
declare(strict_types=1);

namespace Spec\Minds\Core\PWA\Models;

use Minds\Core\PWA\Models\MindsManifest;
use PhpSpec\ObjectBehavior;

class MindsManifestSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(MindsManifest::class);
    }

    public function it_should_build_minds_manifest(): void
    {
        $this->export()->shouldBe([
            "name" => "Minds Web",
            "short_name" => "Minds",
            "description" => "Elevate the global conversation through Internet freedom. Speak freely, protect your privacy, earn crypto, and take back control of your social media",
            "categories" => [
                "social",
                "news",
                "magazines",
            ],
            "theme_color" => "#ffffff",
            "background_color" => "#ffffff",
            "display" => "standalone",
            "android_package_name" => "com.minds.mobile",
            "scope" => "./",
            "start_url" => "/",
            "icons" => [
                [
                    "src" => "/static/en/assets/logos/logo.png",
                    "type" => "image/png",
                    "sizes" => "192x192",
                ],
                [
                    "src" => "/static/en/assets/logos/logo-large.png",
                    "type" => "image/png",
                    "sizes" => "512x512",
                ],
                [
                    "src" => "/static/en/assets/logos/logo-maskable.png",
                    "type" => "image/png",
                    "sizes" => "192x192",
                    "purpose" => "maskable",
                ],
                [
                    "src" => "/static/en/assets/logos/logo-maskable-large.png",
                    "type" => "image/png",
                    "sizes" => "512x512",
                    "purpose" => "maskable",
                ],
            ],
            "prefer_related_applications" => true,
            "related_applications" => [
                [
                    "platform" => "play",
                    "url" => "https://play.google.com/store/apps/details?id=com.minds.mobile",
                    "id" => "com.minds.mobile",
                ],
                [
                    "platform" => "itunes",
                    "url" => "https://apps.apple.com/app/minds-com/id961771928",
                ],
            ]
        ]);
    }
}
