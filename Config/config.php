<?php

return [
    'name'        => 'CDN Bundle by Leuchtfeuer',
    'description' => 'Provides CDN support for emails.',
    'version'     => '1.0.0',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'routes'      => [],
    'services'    => [
        'command' => [],
        'other'   => [
            'mautic.cdn.config' => [
                'class'     => \MauticPlugin\MauticCdnBundle\Integration\Config::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
        ],
        'events'  => [
            'mautic.cdn.subscriber' => [
                'class'     => \MauticPlugin\MauticCdnBundle\EventListener\OnPostSaveSubscriber::class,
                'arguments' => ['mautic.cdn.config', 'mautic.email.model.email', '%mautic.site_url%'],
            ],
        ],
        'forms'        => [],
        'models'       => [],
        'fixtures'     => [],
        'integrations' => [
            'mautic.integration.cdn' => [
                'class' => \MauticPlugin\MauticCdnBundle\Integration\CdnIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            'cdn.integration.configuration' => [
                'class' => \MauticPlugin\MauticCdnBundle\Integration\Support\ConfigSupport::class,
                'tags'  => [
                    'mautic.config_integration',
                ],
            ],
        ],
    ],
];
