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

class OnPostSaveSubscriberTest extends TestCase
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

    /**
     * @dataProvider provideTestData
     */
    public function testReplaceCheck(string $expectedHost, string $fromAddress, bool $hasReplacement): void
    {
        $host       = 'https://site.tld';
        $otherHost  = 'https://other.tld';
        $cdn        = 'https://cdn.a.com';
        $extensions = ['jpg', 'mp4', 'pdf', 'css'];
        $html       = '<body>'.
            '<a href="'.$host.'/file.pdf">Link</a>'.
            '<a href="'.$otherHost.'/file.pdf">Link</a>'.
            '<img src="'.$host.'/image.jpg">'.
            '<img src="'.$otherHost.'/image.jpg">'.
            '<img alt="">'.
            '<video><source src="'.$host.'/video.mp4"></source></video>'.
            '<video><source src="'.$otherHost.'/video.mp4"></source></video>'.
            '<table style="background:#ffffff url(\''.$host.'/img.jpg?version\') center top / auto repeat;"></table>'.
            '<table style="background:#ffffff url(\''.$otherHost.'/img.jpg?version\') center top / auto repeat;"></table>'.
            '<table background="'.$host.'/bg.jpg?version"></table>'.
            '<table background="'.$otherHost.'/bg.jpg?version"></table>'.
            '<img src="'.$host.'/image.gif">'. // This should not be replaced, because .gif is not in the extensions to replace!
            '<img src="'.$otherHost.'/image.gif">'.
            '<link rel="stylesheet" href="'.$host.'/css.css?version" />'.
            '<link rel="stylesheet" href="'.$otherHost.'/css.css?version" />'.
            '<link />'.
            '</body>';
        $replacedHtml = '<body>'.
            '<a href="'.$expectedHost.'/file.pdf">Link</a>'.
            '<a href="'.$otherHost.'/file.pdf">Link</a>'.
            '<img src="'.$expectedHost.'/image.jpg">'.
            '<img src="'.$otherHost.'/image.jpg">'.
            '<img alt="">'.
            '<video><source src="'.$expectedHost.'/video.mp4"></source></video>'.
            '<video><source src="'.$otherHost.'/video.mp4"></source></video>'.
            '<table style="background:#ffffff url(\''.$expectedHost.'/img.jpg?version\') center top / auto repeat;"></table>'.
            '<table style="background:#ffffff url(\''.$otherHost.'/img.jpg?version\') center top / auto repeat;"></table>'.
            '<table background="'.$expectedHost.'/bg.jpg?version"></table>'.
            '<table background="'.$otherHost.'/bg.jpg?version"></table>'.
            '<img src="'.$host.'/image.gif">'.
            '<img src="'.$otherHost.'/image.gif">'.
            '<link rel="stylesheet" href="'.$expectedHost.'/css.css?version">'.
            '<link rel="stylesheet" href="'.$otherHost.'/css.css?version">'.
            '<link>'.
            '</body>';

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn([
                'integration' => [
                    'cdn'         => $cdn,
                    'extensions'  => $extensions,
                    'cdn_replace' => [
                        'b.com'      => 'https://cdn.b.com',
                        'news.c.com' => 'https://news.c.com',
                        'c.com'      => 'https://cdn.c.com',
                        'd.com'      => '',
                        'news.d.com' => 'https://cdn.d.com',
                    ],
                ],
            ]);

        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn($html);
        $email->expects(self::once())
            ->method('getFromAddress')
            ->willReturn($fromAddress);
        $email->expects(self::exactly($hasReplacement ? 1 : 0))
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
        $emailRepository->expects(self::exactly($hasReplacement ? 1 : 0))
            ->method('saveEntity')
            ->with($email);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::exactly($hasReplacement ? 1 : 0))
            ->method('getRepository')
            ->willReturn($emailRepository);

        $subscriber = new OnPostSaveSubscriber($config, $emailModel, $host);
        $subscriber->onPostSave($event);
    }

    public static function provideTestData(): \Generator
    {
        yield 'me@x.com' => ['https://cdn.a.com', 'me@x.com', true];
        yield 'me@b.com' => ['https://cdn.b.com', 'me@b.com', true];
        yield 'me@news.c.com' => ['https://news.c.com', 'me@news.c.com', true];
        yield 'me@c.com' => ['https://cdn.c.com', 'me@c.com', true];
        yield 'me@d.com' => ['https://site.tld', 'me@d.com', false]; // No replacement, keep the site_url
        yield 'me@news.d.com' => ['https://site.tld', 'me@news.d.com', false]; // No replacement, keep the site_url (because more general match above)
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
