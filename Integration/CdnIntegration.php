<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use MauticPlugin\LeuchtfeuerCdnBundle\Form\Type\FeatureSettingsType;

class CdnIntegration extends BasicIntegration implements BasicInterface, ConfigFormFeatureSettingsInterface
{
    use ConfigurationTrait;

    // there's probably a bug that does not allow this to be with the underscore like api_version
    public const NAME         = 'cdn';
    public const DISPLAY_NAME = 'CDN';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/LeuchtfeuerCdnBundle/Assets/img/mautic-cdn-bundle.png';
    }

    public function getFeatureSettingsConfigFormName(): string
    {
        return FeatureSettingsType::class;
    }
}
