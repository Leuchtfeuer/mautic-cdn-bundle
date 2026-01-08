<?php

namespace MauticPlugin\LeuchtfeuerCdnBundle\Tests\Functional;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use Mautic\PluginBundle\Facade\ReloadFacade;
use Mautic\PluginBundle\Helper\IntegrationHelper;

trait EnablePluginTrait
{
    private function enablePlugin(bool $enable): void
    {
        $pluginInstaller = static::getContainer()->get(ReloadFacade::class);
        assert($pluginInstaller instanceof ReloadFacade);
        $pluginInstaller->reloadPlugins();
        $integrationHelper = static::getContainer()->get(IntegrationHelper::class);
        assert($integrationHelper instanceof IntegrationHelper);
        $integration = $integrationHelper->getIntegrationObject('LeuchtfeuerCdn');
        assert($integration instanceof IntegrationInterface);
        $integration->getIntegrationConfiguration()->setIsPublished($enable);
        $doctrine = static::getContainer()->get('doctrine');
        assert($doctrine instanceof ManagerRegistry);
        $doctrine->getManager()->flush();
    }
}
