<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCdnBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticCdnBundle\Integration\CdnIntegration;

class ConfigSupport extends CdnIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
