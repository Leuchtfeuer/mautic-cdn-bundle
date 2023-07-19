<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\LeuchtfeuerCdnBundle\Integration\LeuchtfeuerCdnIntegration;

class ConfigSupport extends LeuchtfeuerCdnIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
