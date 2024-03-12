<?php
declare(strict_types=1);

namespace Minds\Core\PWA\Models;

/**
 * Minds PWA Manifest model.
 */
class MindsManifest extends AbstractPWAManifest
{
    public function __construct()
    {
        parent::__construct(
            name: 'Minds Web',
            shortName: 'Minds',
            description: 'Elevate the global conversation through Internet freedom. Speak freely, protect your privacy, earn crypto, and take back control of your social media',
            backgroundColor: '#ffffff',
            themeColor: '#ffffff',
            categories: ['social', 'news'],
            display: 'standalone',
            androidPackageName: 'com.minds.mobile',
            scope: './',
            startUrl: '/',
            icons: [
                [
                    "src" => "/static/en/assets/logos/logo.png",
                    "type" => "image/png",
                    "sizes" => "192x192"
                ],
                [
                    "src" => "/static/en/assets/logos/logo-large.png",
                    "type" => "image/png",
                    "sizes" => "512x512"
                ],
                [
                    "src" => "/static/en/assets/logos/logo-maskable.png",
                    "type" => "image/png",
                    "sizes" => "192x192",
                    "purpose" => "maskable"
                ],
                [
                    "src" => "/static/en/assets/logos/logo-maskable-large.png",
                    "type" => "image/png",
                    "sizes" => "512x512",
                    "purpose" => "maskable"
                ]
            ],
            preferRelatedApplications: true,
            relatedApplications: [
                [
                    "platform" => "play",
                    "url" => "https://play.google.com/store/apps/details?id=com.minds.mobile",
                    "id" => "com.minds.mobile"
                ],
                [
                    "platform" => "itunes",
                    "url" => "https://apps.apple.com/app/minds-com/id961771928"
                ]
            ]
        );
    }
}
