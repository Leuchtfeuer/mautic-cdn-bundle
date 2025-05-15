<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\Tests\EventListener;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\LeuchtfeuerCdnBundle\EventListener\OnEmailSendSubscriber;
use MauticPlugin\LeuchtfeuerCdnBundle\Integration\Config;
use PHPUnit\Framework\TestCase;

class OnEmailSendSubscriberTest extends TestCase
{
    public function testNoEmailHelper(): void
    {
        $host = 'https://site.tld';

        $event = $this->createMock(EmailSendEvent::class);
        $event->expects(self::once())
            ->method('getHelper')
            ->willReturn(null);
        $event->expects(self::never())
            ->method('getEmail');

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(false);

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }

    public function testNoEmail(): void
    {
        $host = 'https://site.tld';

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::never())
            ->method('setBody');
        $event = $this->createMock(EmailSendEvent::class);
        $event->expects(self::once())
            ->method('getHelper')
            ->willReturn($emailHelper);
        $event->expects(self::once())
            ->method('getEmail')
            ->willReturn(null);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(false);

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }

    public function testEmailNoId(): void
    {
        $host = 'https://site.tld';

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::never())
            ->method('setBody');
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getId')
            ->willReturn(null);
        $event = $this->createMock(EmailSendEvent::class);
        $event->expects(self::once())
            ->method('getHelper')
            ->willReturn($emailHelper);
        $event->expects(self::once())
            ->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(false);

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }

    public function testNotEnabled(): void
    {
        $host    = 'https://site.tld';
        $emailId = 7262626;

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::never())
            ->method('setBody');
        $email = $this->createMock(Email::class);
        $email->method('getId')
            ->willReturn($emailId);
        $event = $this->createMock(EmailSendEvent::class);
        $event->expects(self::once())
            ->method('getHelper')
            ->willReturn($emailHelper);
        $event->expects(self::once())
            ->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->expects(self::once())
            ->method('isPublished')
            ->willReturn(false);

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }

    public function testNoIntegrationSettings(): void
    {
        $host    = 'https://site.tld';
        $emailId = 7262626;

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn([]);

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::never())
            ->method('setBody');
        $email = $this->createMock(Email::class);
        $email->method('getId')
            ->willReturn($emailId);
        $event = $this->createMock(EmailSendEvent::class);
        $event->method('getHelper')
            ->willReturn($emailHelper);
        $event->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->expects(self::once()) // To check the early caching.
            ->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::never())
            ->method('getRepository');

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);

        // Call once again with the same parameters to check "early caching"
        $subscriber->onSend($event);
    }

    /**
     * @param array<mixed> $settings
     * @dataProvider emptyCdnProvider
     */
    public function testEmptyCdnSetting(array $settings): void
    {
        $host    = 'https://site.tld';
        $emailId = 352111;

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn(['integration' => $settings]);

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::never())
            ->method('setBody');
        $email = $this->createMock(Email::class);
        $email->method('getId')
            ->willReturn($emailId);
        $event = $this->createMock(EmailSendEvent::class);
        $event->method('getHelper')
            ->willReturn($emailHelper);
        $event->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::never())
            ->method('getRepository');

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }

    public static function emptyCdnProvider(): \Generator
    {
        yield 'not isset cdn' => [['extensions' => []]];
        yield 'empty cdn' => [['cdn' => '', 'extensions' => []]];
    }

    public function testReplaceWithDefault(): void
    {
        $emailId    = 22341;
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
        $email->method('getId')
            ->willReturn($emailId);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn($html);
        $email->expects(self::once())
            ->method('getFromAddress')
            ->willReturn('some@strange.domain.com');
        $email->expects(self::never())
            ->method('setCustomHtml');

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::once())
            ->method('setBody')
            ->with($replacedHtml);
        $event = $this->createMock(EmailSendEvent::class);
        $event->method('getHelper')
            ->willReturn($emailHelper);
        $event->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }

    public function testReplaceDifferentSlashes(): void
    {
        $emailId    = 22341;
        $host       = 'https://site.tld';
        $cdn        = 'https://cdn.tld/';
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
        $email->method('getId')
            ->willReturn($emailId);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn($html);
        $email->expects(self::once())
            ->method('getFromAddress')
            ->willReturn('some@strange.domain.com');
        $email->expects(self::never())
            ->method('setCustomHtml');

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::once())
            ->method('setBody')
            ->with($replacedHtml);
        $event = $this->createMock(EmailSendEvent::class);
        $event->method('getHelper')
            ->willReturn($emailHelper);
        $event->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }

    public function testReplaceWithSpecific(): void
    {
        $emailId    = 7262626;
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
        $email->method('getId')
            ->willReturn($emailId);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn($html);
        $email->expects(self::once())
            ->method('getFromAddress')
            ->willReturn('some@strange.domain.com');
        $email->expects(self::never())
            ->method('setCustomHtml');

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::once())
            ->method('setBody')
            ->with($replacedHtml);
        $event = $this->createMock(EmailSendEvent::class);
        $event->method('getHelper')
            ->willReturn($emailHelper);
        $event->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }

    /**
     * @dataProvider provideTestData
     */
    public function testReplaceCheck(string $expectedHost, string $fromAddress, bool $hasReplacement): void
    {
        $emailId    = 7262626;
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
                        'sub.f.com'  => null,
                        'f.com'      => 'https://f.com',
                    ],
                ],
            ]);

        $email = $this->createMock(Email::class);
        $email->method('getId')
            ->willReturn($emailId);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn($html);
        $email->expects(self::once())
            ->method('getFromAddress')
            ->willReturn($fromAddress);
        $email->expects(self::never())
            ->method('setCustomHtml');

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::exactly($hasReplacement ? 1 : 0))
            ->method('setBody')
            ->with($replacedHtml);
        $event = $this->createMock(EmailSendEvent::class);
        $event->method('getHelper')
            ->willReturn($emailHelper);
        $event->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }

    public static function provideTestData(): \Generator
    {
        yield 'me@x.com' => ['https://cdn.a.com', 'me@x.com', true];
        yield 'me@b.com' => ['https://cdn.b.com', 'me@b.com', true];
        yield 'me@news.c.com' => ['https://news.c.com', 'me@news.c.com', true];
        yield 'me@c.com' => ['https://cdn.c.com', 'me@c.com', true];
        yield 'me@d.com' => ['https://site.tld', 'me@d.com', false]; // No replacement, keep the site_url
        yield 'me@news.d.com' => ['https://site.tld', 'me@news.d.com', false]; // No replacement, keep the site_url (because more general match above)
        yield 'me@sub.f.com' => ['https://site.tld', 'me@sub.f.com', false]; // No replacement, keep the site_url (because CDN is empty)
        yield 'me@f.com' => ['https://f.com', 'me@f.com', true]; // Replace, because we send from not-subdomain.
    }

    public function testNoReplaceWithSpecificEmpty(): void
    {
        $emailId    = 7262626;
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
        $email->method('getId')
            ->willReturn($emailId);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn('<body>');
        $email->expects(self::once())
            ->method('getFromAddress')
            ->willReturn('some@strange.domain.com');
        $email->expects(self::never())
            ->method('setCustomHtml');

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::never())
            ->method('setBody');
        $event = $this->createMock(EmailSendEvent::class);
        $event->method('getHelper')
            ->willReturn($emailHelper);
        $event->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }

    public function testEmptyHtml(): void
    {
        $emailId    = 7262626;
        $host       = 'https://site.tld';
        $cdn        = 'https://cdn.tld';
        $extensions = ['jpg', 'mp4', 'pdf'];

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')
            ->willReturn(['integration' => ['cdn' => $cdn, 'extensions' => $extensions]]);

        $email = $this->createMock(Email::class);
        $email->method('getId')
            ->willReturn($emailId);
        $email->expects(self::once())
            ->method('getCustomHtml')
            ->willReturn('');
        $email->expects(self::never())
            ->method('setCustomHtml');

        $emailHelper = $this->createMock(MailHelper::class);
        $emailHelper->expects(self::never())
            ->method('setBody');
        $event = $this->createMock(EmailSendEvent::class);
        $event->method('getHelper')
            ->willReturn($emailHelper);
        $event->method('getEmail')
            ->willReturn($email);

        $config = $this->createMock(Config::class);
        $config->method('isPublished')
            ->willReturn(true);
        $config->method('getIntegrationEntity')
            ->willReturn($integration);

        $emailModel = $this->createMock(EmailModel::class);
        $emailModel->expects(self::never())
            ->method('getRepository');

        $subscriber = new OnEmailSendSubscriber($config, $host);
        $subscriber->onSend($event);
    }
}
