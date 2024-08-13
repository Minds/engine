<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Email\V2\Common;

use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Common\EmailStyles;
use Minds\Core\Email\V2\Common\EmailStylesV2;
use Minds\Core\Email\V2\Common\Template;
use Minds\Core\I18n\Translator;
use Minds\Core\Markdown\Markdown;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class TemplateSpec extends ObjectBehavior
{
    private Collaborator $markdownMock;
    private Collaborator $emailStylesMock;
    private Collaborator $emailStylesV2Mock;
    private Collaborator $configMock;
    private Collaborator $translatorMock;
    public $data = [];

    public function let(
        Markdown $markdownMock,
        EmailStyles $emailStylesMock,
        EmailStylesV2 $emailStylesV2Mock,
        Translator $translatorMock,
        Config $configMock
    ) {
        $this->beConstructedWith(
            $markdownMock,
            $configMock,
            $emailStylesV2Mock,
            $translatorMock,
            $emailStylesV2Mock
        );
        $this->markdownMock = $markdownMock;
        $this->emailStylesMock = $emailStylesMock;
        $this->emailStylesV2Mock = $emailStylesV2Mock;
        $this->configMock = $configMock;
        $this->translatorMock = $translatorMock;
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Template::class);
    }

    public function it_should_reinit_constructor_values_when_clearing_data(): void {
        $siteUrl = 'https://tenant.minds.io/';
        $cdnAssetsUrl = 'https://cdn-assets.tenant.minds.io/front/dist/';
        $cdnUrl = 'https://cdn.tenant.minds.io/';

        $this->data = [
            'foo' => 1,
            'bar' => 2
        ];

        $this->configMock->get('site_url')
            ->shouldBeCalled()
            ->willReturn($siteUrl);

        $this->configMock->get('cdn_assets_url')
            ->shouldBeCalled()
            ->willReturn($cdnAssetsUrl);

        $this->configMock->get('cdn_url')
            ->shouldBeCalled()
            ->willReturn($cdnUrl);

        $this->clear()->shouldBe([
            'site_url' => $siteUrl,
            'cdn_assets_url' => $cdnAssetsUrl,
            'cdn_url' => $cdnUrl,
            'translator' => $this->translatorMock
        ]);

        $this->configMock->get('site_url')
            ->shouldHaveBeenCalledTimes(2);

        $this->configMock->get('cdn_assets_url')
            ->shouldHaveBeenCalledTimes(2);
        
        $this->configMock->get('cdn_url')
            ->shouldHaveBeenCalledTimes(2);
    }

    public function it_should_NOT_reinit_constructor_values_when_clearing_data_when_full_clear_is_passed(): void {
        $this->data = [
            'foo' => 1,
            'bar' => 2
        ];

        $this->clear(true)->shouldBe([]);

        $this->configMock->get('site_url')
            ->shouldBeCalledTimes(1); // during init

        $this->configMock->get('cdn_assets_url')
            ->shouldBeCalledTimes(1); // during init
        
        $this->configMock->get('cdn_url')
            ->shouldBeCalledTimes(1); // during init
    }
}
