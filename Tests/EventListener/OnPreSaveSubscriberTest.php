<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\Tests\EventListener;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\LeuchtfeuerCdnBundle\EventListener\OnPostSaveSubscriber;
use MauticPlugin\LeuchtfeuerCdnBundle\Integration\Config;
use PHPUnit\Framework\TestCase;

class OnPreSaveSubscriberTest extends TestCase
{
    public function testNotEnabled(): void
    {
        $host = 'https://site.tld';

        $event = $this->createMock(EmailEvent::class);
        $event->expects(self::never())
            ->method('getEmail');

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(false);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::never())
            ->method('getRepository');

        $subscriber = new OnPostSaveSubscriber($config, $emailModel, $host);
        $subscriber->onPostSave($event);
    }

    public function testNoIntegrationSettings(): void
    {
        $host = 'https://site.tld';

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn([]);

        $event = $this->createMock(EmailEvent::class);
        $event->expects(self::never())
            ->method('getEmail');

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::never())
            ->method('getRepository');

        $subscriber = new OnPostSaveSubscriber($config, $emailModel, $host);
        $subscriber->onPostSave($event);
    }

    /**
     * @param array<mixed> $settings
     * @dataProvider emptyCdnProvider
     */
    public function testEmptyCdnSetting(array $settings): void
    {
        $host = 'https://site.tld';

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn(['integration' => $settings]);

        $event = $this->createMock(EmailEvent::class);
        $event->expects(self::never())
            ->method('getEmail');

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::never())
            ->method('getRepository');

        $subscriber = new OnPostSaveSubscriber($config, $emailModel, $host);
        $subscriber->onPostSave($event);
    }

    public static function emptyCdnProvider(): \Generator
    {
        yield 'not isset cdn' => [['extensions' => []]];
        yield 'empty cdn' => [['cdn' => '', 'extensions' => []]];
    }

    public function testReplaceWithDefault(): void
    {
        $host       = 'https://site.tld';
        $cdn        = 'https://cdn.tld';
        $extensions = ['jpg', 'mp4', 'pdf', 'css'];
        $html       = '<body><a href="https://site.tld/file.pdf">Link</a>'.
            '<img src="https://site.tld/image.jpg">'.
            '<video><source src="https://site.tld/video.mp4"></source></video>'.
            '<img src="https://site.tld/image.gif">'.
            '<link rel="stylesheet" href="https://site.tld/css.css?version" />'.
            '<a href="https://site.tld/#">Link2</a></body>';
        $replacedHtml = '<body><a href="https://cdn.tld/file.pdf">Link</a>'.
            '<img src="https://cdn.tld/image.jpg">'.
            '<video><source src="https://cdn.tld/video.mp4"></source></video>'.
            '<img src="https://site.tld/image.gif">'.
            '<link rel="stylesheet" href="https://cdn.tld/css.css?version">'.
            '<a href="https://site.tld/#">Link2</a></body>';

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn([
                'integration' => [
                    'cdn'         => $cdn,
                    'extensions'  => $extensions,
                    'cdn_replace' => ['some.com' => 'https://some.cdn'],
                ],
            ]);

        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn($html);
        $email->expects(self::once())
            ->method('getFromAddress')
            ->willReturn('some@strange.domain.com');
        $email->expects(self::once())
            ->method('setCustomHtml')
            ->with($replacedHtml);

        $event = $this->createMock(EmailEvent::class);
        $event->expects(self::once())
            ->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $emailRepository = $this->createMock(EmailRepository::class);
        $emailRepository->expects(self::once())
            ->method('saveEntity')
            ->with($email);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::once())
            ->method('getRepository')
            ->willReturn($emailRepository);

        $subscriber = new OnPostSaveSubscriber($config, $emailModel, $host);
        $subscriber->onPostSave($event);
    }

    public function testReplaceWithSpecific(): void
    {
        $host       = 'https://site.tld';
        $cdn        = 'https://cdn.tld';
        $extensions = ['jpg', 'mp4', 'pdf', 'css'];
        $html       = '<body><a href="https://site.tld/file.pdf">Link</a>'.
            '<img src="https://site.tld/image.jpg">'.
            '<img src="https://other.tld/image.jpg">'. // test for different domain
            '<img alt="">'.
            '<video><source src="https://site.tld/video.mp4"></source></video>'.
            '<table style="background:#ffffff url(\'https://site.tld/img.jpg?version\') center top / auto repeat;"></table>'.
            '<table background="https://site.tld/bg.jpg?version"></table>'.
            '<img src="https://site.tld/image.gif">'.
            '<link rel="stylesheet" href="https://site.tld/css.css?version" />'.
            '<link />'. // test for an empty attribute
            '<a href="https://site.tld/#">Link2</a></body>';
        $replacedHtml = '<body><a href="https://some.cdn/file.pdf">Link</a>'.
            '<img src="https://some.cdn/image.jpg">'.
            '<img src="https://other.tld/image.jpg">'. // test for different domain
            '<img alt="">'.
            '<video><source src="https://some.cdn/video.mp4"></source></video>'.
            '<table style="background:#ffffff url(\'https://some.cdn/img.jpg?version\') center top / auto repeat;"></table>'.
            '<table background="https://some.cdn/bg.jpg?version"></table>'.
            '<img src="https://site.tld/image.gif">'.
            '<link rel="stylesheet" href="https://some.cdn/css.css?version">'.
            '<link>'. // test for an empty attribute
            '<a href="https://site.tld/#">Link2</a></body>';

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn([
                'integration' => [
                    'cdn'         => $cdn,
                    'extensions'  => $extensions,
                    'cdn_replace' => ['domain.com' => 'https://some.cdn'],
                ],
            ]);

        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn($html);
        $email->expects(self::once())
            ->method('getFromAddress')
            ->willReturn('some@strange.domain.com');
        $email->expects(self::once())
            ->method('setCustomHtml')
            ->with($replacedHtml);

        $event = $this->createMock(EmailEvent::class);
        $event->expects(self::once())
            ->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $emailRepository = $this->createMock(EmailRepository::class);
        $emailRepository->expects(self::once())
            ->method('saveEntity')
            ->with($email);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::once())
            ->method('getRepository')
            ->willReturn($emailRepository);

        $subscriber = new OnPostSaveSubscriber($config, $emailModel, $host);
        $subscriber->onPostSave($event);
    }

    public function testNoReplaceWithSpecificEmpty(): void
    {
        $host       = 'https://site.tld';
        $cdn        = 'https://cdn.tld';
        $extensions = ['jpg', 'mp4', 'pdf', 'css'];

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn([
                'integration' => [
                    'cdn'         => $cdn,
                    'extensions'  => $extensions,
                    'cdn_replace' => ['domain.com' => ''],
                ],
            ]);

        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn('<body>');
        $email->expects(self::once())
            ->method('getFromAddress')
            ->willReturn('some@strange.domain.com');
        $email->expects(self::never())
            ->method('setCustomHtml');

        $event = $this->createMock(EmailEvent::class);
        $event->expects(self::once())
            ->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::never())
            ->method('getRepository');

        $subscriber = new OnPostSaveSubscriber($config, $emailModel, $host);
        $subscriber->onPostSave($event);
    }

    public function testEmptyHtml(): void
    {
        $host       = 'https://site.tld';
        $cdn        = 'https://cdn.tld';
        $extensions = ['jpg', 'mp4', 'pdf'];

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn(['integration' => ['cdn' => $cdn, 'extensions' => $extensions]]);

        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn('');
        $email->expects(self::never())
            ->method('setCustomHtml');

        $event = $this->createMock(EmailEvent::class);
        $event->expects(self::once())
            ->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::never())
            ->method('getRepository');

        $subscriber = new OnPostSaveSubscriber($config, $emailModel, $host);
        $subscriber->onPostSave($event);
    }
}
