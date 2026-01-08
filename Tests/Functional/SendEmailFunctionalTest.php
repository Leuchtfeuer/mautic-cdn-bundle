<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SendEmailFunctionalTest extends MauticMysqlTestCase
{
    use EnablePluginTrait;

    protected function setUp(): void
    {
        // When the test case is first in a row, the Mautic re-creates Symfony Kernel with $this->configParams,
        // but in later tests we need original values.
        $originalConfigParams                         = $this->configParams;
        $this->configParams['disable_trackable_urls'] = true;
        parent::setUp();
        $this->configParams = $originalConfigParams;
    }

    /**
     * @dataProvider provideDomains
     */
    public function testSendingMultipleEmailsPerSegment(string $fromDomain): void
    {
        $this->enablePlugin(true);

        $fromEmail       = 'test@whatever.com';
        $siteDomain      = $this->configParams['site_url'];
        $mainCDN         = 'https://cdn.a.com';
        $replaceFromCdn1 = 'b.com';
        $replaceToCdn1   = 'https://cdn.b.com';
        $emailSubject    = 'Subject A';

        $replaceToCDN = $mainCDN;

        if ('localhost' !== $fromDomain) {
            $fromEmail    = 'test@'.$fromDomain;
            $replaceToCDN = $replaceToCdn1;
        }

        // fill in plugin_integration_settings with defaults.
        $this->client->request(Request::METHOD_GET, '/s/plugins');
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());

        $crawler        = $this->client->request(Request::METHOD_GET, '/s/plugins/config/leuchtfeuercdn');
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());

        $form = $crawler->filter('form[name="integration_config"]')->form([
            'integration_config' => [
                'isPublished'     => '1',
                'featureSettings' => [
                    'integration' => [
                        'cdn' => $mainCDN,
                    ],
                ],
            ],
        ]);

        $values = $form->getPhpValues();

        $integrationSettings = $values['integration_config']['featureSettings']['integration'];

        $integrationSettings['extensions'] = ['jpg', 'jpeg', 'png', 'pdf'];

        $integrationSettings['cdn_replace']           = [];
        $integrationSettings['cdn_replace']['list']   = [];
        $integrationSettings['cdn_replace']['list'][] = [
            'label' => $replaceFromCdn1,
            'value' => $replaceToCdn1,
        ];
        $integrationSettings['cdn_replace']['list'][] = [
            'label' => 'a.sub.com',
            'value' => '',
        ];

        $values['integration_config']['featureSettings']['integration'] = $integrationSettings;

        $this->client->request($form->getMethod(), $form->getUri(), $values);
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());

        // More variants are tested in the unit test:
        // \MauticPlugin\LeuchtfeuerCdnBundle\Tests\Unit\EventListener\OnEmailSendSubscriberTest::testReplaceCheck
        $emailHtml = '<body><a href="'.$siteDomain.'/file1.pdf">Link</a>'.
            '<img src="'.$siteDomain.'/image2.jpg" alt="">'.
            '<img src="https://'.$replaceFromCdn1.'/image3.jpg" alt="">'.
            '<video><source src="'.$siteDomain.'/video4.mp4"></source></video>'.
            '<img src="'.$siteDomain.'/image5.gif" alt="">'.
            '<link rel="stylesheet" href="'.$siteDomain.'/css6.css?version" />'.
            '<link rel="stylesheet" href="https://{webview_url}/css7.css?version" />'.
            '<a href="#{field=!@#$%^&*()-=+|[]}">The link with token</a>'.
            '<span>{field=!@#$%^&*()-=+|[]}</span>'. // The token within the HTML
            '<a href="#{contactfield=email|true}">The link with email token</a>'.
            '<a href="https://other.tld/#8">Link2 a.com</a>'.
            '<a href="https://{webview_url}">Link2 a.com</a>'.
            '<a href="'.$siteDomain.'/#9">Link2</a></body>';

        $replacedHtml = '<head><title>'.$emailSubject.'</title></head><body><a href="'.$replaceToCDN.'/file1.pdf">Link</a>'.
            '<img src="'.$replaceToCDN.'/image2.jpg" alt="">'.
            '<img src="https://'.$replaceFromCdn1.'/image3.jpg" alt="">'. // do not replace direct links, only site_url
            '<video><source src="'.$siteDomain.'/video4.mp4"></source></video>'.
            '<img src="'.$siteDomain.'/image5.gif" alt="">'.
            '<link rel="stylesheet" href="'.$siteDomain.'/css6.css?version">'.
            '<link rel="stylesheet" href="https://https://localhost/email/view/~{token}~/css7.css?version">'.
            '<a href="#{field=!@#$%^&amp;*()-=+|[]}">The link with token</a>'.
            '<span>{field=!@#$%^&amp;*()-=+|[]}</span>'. // The token within the HTML
            '<a href="#{contactfield=email|true}">The link with email token</a>'.
            '<a href="https://other.tld/#8">Link2 a.com</a>'.
            '<a href="https://https://localhost/email/view/~{token}~">Link2 a.com</a>'.
            '<a href="'.$siteDomain.'/#9">Link2</a></body>';

        $segment = $this->createSegment('segment-a');
        $email   = $this->createSpecificEmail(
            $fromEmail,
            $emailSubject,
            $emailHtml,
            $segment
        );

        foreach (['contact@one.email', 'contact@two.email', 'contact@three.email'] as $emailAddress) {
            $contact = new Lead();
            $contact->setEmail($emailAddress);

            $listContact = new ListLead();
            $listContact->setLead($contact);
            $listContact->setList($segment);
            $listContact->setDateAdded(new \DateTime());

            $this->em->persist($listContact);
            $this->em->persist($contact);
        }
        $this->em->flush();

        $this->client->request(Request::METHOD_POST, '/s/ajax?action=email:sendBatch', [
            'id'         => $email->getId(),
            'pending'    => 3,
            'batchLimit' => 10,
        ]);

        Assert::assertTrue($this->client->getResponse()->isOk(), (string) $this->client->getResponse()->getContent());
        Assert::assertSame('{"success":1,"percent":100,"progress":[3,3],"stats":{"sent":3,"failed":0,"failedRecipients":[]}}', $this->client->getResponse()->getContent());
        self::assertQueuedEmailCount(3);

        $emailIndex = 0;
        $sentEmail  = self::getMailerMessage($emailIndex);
        Assert::assertInstanceOf(MauticMessage::class, $sentEmail);
        Assert::assertSame($emailSubject, $sentEmail->getSubject());
        $htmlBody        = $sentEmail->getHtmlBody();
        Assert::assertIsString($htmlBody);
        $htmlBodyNoToken = preg_replace('/<img height="1" width="1"[^>]+>/', '', $htmlBody);
        Assert::assertIsString($htmlBodyNoToken);
        $leadIdHash = $sentEmail->getLeadIdHash();
        Assert::assertIsString($leadIdHash);
        $assertedHtml = str_replace(
            [
                '~{token}~',
                '{contactfield=email|true}',
            ],
            [
                $leadIdHash,
                urlencode($sentEmail->getTo()[0]->getEncodedAddress()),
            ],
            $replacedHtml
        );
        Assert::assertIsString($assertedHtml);
        Assert::assertSame($assertedHtml, $htmlBodyNoToken);

        $emailIndex = 1;
        $sentEmail  = self::getMailerMessage($emailIndex);
        Assert::assertInstanceOf(\Symfony\Component\Mime\Email::class, $sentEmail);
        Assert::assertSame($emailSubject, $sentEmail->getSubject());
        $htmlBody        = $sentEmail->getHtmlBody();
        Assert::assertIsString($htmlBody);
        $htmlBodyNoToken = preg_replace('/<img height="1" width="1"[^>]+>/', '', $htmlBody);
        Assert::assertIsString($htmlBodyNoToken);
        $leadIdHash = $sentEmail->getLeadIdHash();
        Assert::assertIsString($leadIdHash);
        $assertedHtml = str_replace(
            [
                '~{token}~',
                '{contactfield=email|true}',
            ],
            [
                $leadIdHash,
                urlencode($sentEmail->getTo()[0]->getEncodedAddress()),
            ],
            $replacedHtml
        );
        Assert::assertIsString($assertedHtml);
        Assert::assertSame($assertedHtml, $htmlBodyNoToken);

        $emailIndex = 2;
        $sentEmail  = self::getMailerMessage($emailIndex);
        Assert::assertInstanceOf(\Symfony\Component\Mime\Email::class, $sentEmail);
        Assert::assertSame($emailSubject, $sentEmail->getSubject());
        $htmlBody        = $sentEmail->getHtmlBody();
        Assert::assertIsString($htmlBody);
        $htmlBodyNoToken = preg_replace('/<img height="1" width="1"[^>]+>/', '', $htmlBody);
        Assert::assertIsString($htmlBodyNoToken);
        $leadIdHash = $sentEmail->getLeadIdHash();
        Assert::assertIsString($leadIdHash);
        $assertedHtml = str_replace(
            [
                '~{token}~',
                '{contactfield=email|true}',
            ],
            [
                $leadIdHash,
                urlencode($sentEmail->getTo()[0]->getEncodedAddress()),
            ],
            $replacedHtml
        );
        Assert::assertIsString($assertedHtml);
        Assert::assertSame($assertedHtml, $htmlBodyNoToken);
    }

    public static function provideDomains(): \Generator
    {
        yield 'default' => ['localhost'];
        yield 'b.com' => ['b.com'];
    }

    /**
     * @param array<mixed> $defaultConfigOptions
     */
    protected function setUpSymfony(array $defaultConfigOptions = []): void
    {
        $defaultConfigOptions['disable_trackable_urls'] = true;

        parent::setUpSymfony($defaultConfigOptions);
    }

    /**
     * Replace with \Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait when 5.0+.
     */
    private function createSegment(string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setAlias($alias);
        $segment->setName($alias);
        $segment->setPublicName($alias);
        $this->em->persist($segment);

        return $segment;
    }

    private function createEmail(string $name): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject('Test Subject');
        $email->setIsPublished(true);

        $this->em->persist($email);

        return $email;
    }

    private function createSpecificEmail(string $fromEmail, string $subject, string $customHtml, LeadList $segment): Email
    {
        $email = $this->createEmail($subject);

        $email->setFromAddress($fromEmail);
        $email->setSubject($subject);
        $email->setEmailType('list');
        $email->setCustomHtml($customHtml);
        $email->addList($segment);

        return $email;
    }
}
