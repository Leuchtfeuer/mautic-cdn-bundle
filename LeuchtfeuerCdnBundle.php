<?php

namespace MauticPlugin\LeuchtfeuerCdnBundle;

use Mautic\IntegrationsBundle\Bundle\AbstractPluginBundle;
use MauticPlugin\LeuchtfeuerCdnBundle\DependencyInjection\Compiler\TwigFormThemePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LeuchtfeuerCdnBundle extends AbstractPluginBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TwigFormThemePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
    }
}
