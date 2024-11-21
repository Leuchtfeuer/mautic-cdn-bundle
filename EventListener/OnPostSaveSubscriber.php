<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\EventListener;

use DOMAttr;
use DOMElement;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\LeuchtfeuerCdnBundle\Integration\Config;
use RuntimeException;
use Symfony\Component\DomCrawler\AbstractUriElement;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Image;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OnPostSaveSubscriber implements EventSubscriberInterface
{
    private Config $config;

    private EmailModel $emailModel;

    private string $siteUrl;

    public function __construct(Config $config, EmailModel $emailModel, string $host)
    {
        $this->config     = $config;
        $this->emailModel = $emailModel;
        $this->siteUrl    = $host;
    }

    public function onPostSave(EmailEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $integrationSettings = $this->config->getIntegrationEntity()->getFeatureSettings();
        assert(is_array($integrationSettings));
        if (!isset($integrationSettings['integration'])) {
            return;
        }
        $settings = $integrationSettings['integration'];

        if (!isset($settings['cdn']) || '' === $settings['cdn']) {
            return;
        }

        $email = $event->getEmail();
        $html  = $email->getCustomHtml();
        assert(is_string($html));

        if ('' === $html) {
            return;
        }

        $cdn        = $settings['cdn'];
        $extensions = $settings['extensions'];

        // The null value is when the "example" email is sent.
        $mailFrom = $email->getFromAddress();
        if (is_string($mailFrom) && false !== $atPosition = strrpos($mailFrom, '@')) {
            $sentFromDomain = substr($mailFrom, $atPosition);
            $senderCdn      = $settings['cdn_replace'] ?? [];
            foreach ($senderCdn as $sender => $cdnLink) {
                if (!str_contains($sentFromDomain, $sender)) {
                    continue;
                }

                $cdn = $cdnLink;
            }
        }

        if ('' === $cdn) {
            return;
        }

        $extensionsQuoted = array_map(static function (string $extension): string {
            return preg_quote($extension, '/');
        }, $extensions);
        $extensionsRegex = '/(?:'.implode('|', $extensionsQuoted).')(?:|\?[\w]*)(?:$|\'|")/';

        // no regex for HTML https://stackoverflow.com/a/1732454
        $crawler = new Crawler(null, null, $this->siteUrl);
        $crawler->addHtmlContent($html);

        $links = $crawler->filter('a');
        if ($links->count() > 0) {
            $this->replace($links->links(), $extensionsRegex, $cdn);
        }

        $images = $crawler->filter('img');
        if ($images->count() > 0) {
            $this->replace($images->images(), $extensionsRegex, $cdn);
        }

        $this->replaceElement($crawler->filter('source'), $extensionsRegex, $cdn, 'src');
        $this->replaceElement($crawler->filter('link'), $extensionsRegex, $cdn, 'href');
        $this->replaceElement($crawler->filter('[style]'), $extensionsRegex, $cdn, 'style');
        $this->replaceElement($crawler->filter('[background]'), $extensionsRegex, $cdn, 'background');
        $html = $crawler->html();

        $email->setCustomHtml($html);
        $this->emailModel->getRepository()->saveEntity($email);
    }

    /**
     * @return array<string, array<int, string|int>|string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_POST_SAVE => ['onPostSave', -255],
        ];
    }

    /**
     * @param array<int, AbstractUriElement> $elements
     */
    private function replace(array $elements, string $extensionsRegex, string $cdn): void
    {
        foreach ($elements as $element) {
            $href = $element->getUri();

            if (null === $href) {
                continue;
            }

            if (false === strpos($href, $this->siteUrl)) {
                continue;
            }

            if (1 !== preg_match($extensionsRegex, $href)) {
                continue;
            }

            if ($element instanceof Link) {
                $element->getNode()->setAttribute('href', str_replace($this->siteUrl, $cdn, $href));
            } elseif ($element instanceof Image) {
                $element->getNode()->setAttribute('src', str_replace($this->siteUrl, $cdn, $href));
            } else {
                throw new RuntimeException('The item should be either Link or Image.');
            }
        }
    }

    private function replaceElement(Crawler $elements, string $extensionsRegex, string $cdn, string $attribute): void
    {
        $elements->each(function (Crawler $crawler) use ($extensionsRegex, $cdn, $attribute): void {
            $node = $crawler->getNode(0);
            if (!$node instanceof DOMElement || null === $node->attributes) {
                return;
            }

            $hrefAttribute = $node->attributes->getNamedItem($attribute);

            if (!$hrefAttribute instanceof DOMAttr) {
                return;
            }

            if (false === strpos($hrefAttribute->value, $this->siteUrl)) {
                return;
            }

            if (1 !== preg_match($extensionsRegex, $hrefAttribute->value)) {
                return;
            }

            $hrefAttribute->value = str_replace($this->siteUrl, $cdn, $hrefAttribute->value);
        });
    }
}
