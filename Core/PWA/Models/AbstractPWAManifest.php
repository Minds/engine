<?php
declare(strict_types=1);

namespace Minds\Core\PWA\Models;

use Minds\Entities\ExportableInterface;

/**
 * Abstract PWA Manifest model.
 */
abstract class AbstractPWAManifest implements ExportableInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $shortName,
        private readonly string $description,
        private readonly array $categories,
        private readonly string $themeColor,
        private readonly string $backgroundColor,
        private readonly string $display,
        private readonly string $scope,
        private readonly string $startUrl,
        private readonly array $icons,
        private readonly bool $preferRelatedApplications,
        private readonly ?array $relatedApplications = null,
        private readonly ?string $androidPackageName = null,
    ) {
    }

    /**
     * Export manifest in suitable format.
     * @param array $extras - extra variables to export.
     * @return array - exported manifest.
     */
    public function export(array $extras = []): array
    {
        $export = [];

        if ($this->name) {
            $export['name'] = $this->name;
        }
        if ($this->shortName) {
            $export['short_name'] = $this->shortName;
        }
        if ($this->description) {
            $export['description'] = $this->description;
        }
        if ($this->categories && count($this->categories) > 0) {
            $export['categories'] = $this->categories;
        }
        if ($this->themeColor) {
            $export['theme_color'] = $this->themeColor;
        }
        if ($this->backgroundColor) {
            $export['background_color'] = $this->backgroundColor;
        }
        if ($this->display) {
            $export['display'] = $this->display;
        }
        if ($this->androidPackageName) {
            $export['android_package_name'] = $this->androidPackageName;
        }
        if ($this->scope) {
            $export['scope'] = $this->scope;
        }
        if ($this->startUrl) {
            $export['start_url'] = $this->startUrl;
        }
        if ($this->icons && count($this->icons) > 0) {
            $export['icons'] = $this->icons;
        }
        if (isset($this->preferRelatedApplications)) {
            $export['prefer_related_applications'] = $this->preferRelatedApplications;
        }
        if ($this->relatedApplications) {
            $export['related_applications'] = $this->relatedApplications;
        }

        return $export;
    }
}
