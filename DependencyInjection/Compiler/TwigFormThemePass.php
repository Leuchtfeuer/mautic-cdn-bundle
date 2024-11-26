<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwigFormThemePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('twig.form.resources')) {
            return;
        }

        $formThemes = $container->getParameter('twig.form.resources');

        if (!is_array($formThemes)) {
            throw new \LogicException('Container parameter "twig.form.resources" must be an array.');
        }

        $formThemes[] = '@LeuchtfeuerCdn/FormTheme/Custom/form_theme.html.twig';

        $container->setParameter('twig.form.resources', $formThemes);
    }
}
