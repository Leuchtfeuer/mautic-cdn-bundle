<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCdnBundle\Tests\EventListener;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticCdnBundle\EventListener\OnPostSaveSubscriber;
use MauticPlugin\MauticCdnBundle\Integration\Config;
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

    public function testReplace(): void
    {
        $host       = 'https://site.tld';
        $cdn        = 'https://cdn.tld';
        $extensions = ['jpg', 'mp4', 'pdf'];
        $html       = '<body><a href="https://site.tld/file.pdf">Link</a>'.
            '<img src="https://site.tld/image.jpg">'.
            '<video><source src="https://site.tld/video.mp4"></source></video>'.
            '<img src="https://site.tld/image.gif">'.
            '<a href="https://site.tld/#">Link2</a></body>';
        $replacedHtml = '<body><a href="https://cdn.tld/file.pdf">Link</a>'.
            '<img src="https://cdn.tld/image.jpg">'.
            '<video><source src="https://cdn.tld/video.mp4"></source></video>'.
            '<img src="https://site.tld/image.gif">'.
            '<a href="https://site.tld/#">Link2</a></body>';

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn(['integration' => ['cdn' => $cdn, 'extensions' => $extensions]]);

        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn($html);
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
}
