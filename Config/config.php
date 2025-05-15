<?php

return [
    'name'        => 'CDN email support by Leuchtfeuer',
    'description' => 'Provides CDN support for emails.',
    'version'     => '3.0.0',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'routes'      => [],
    'services'    => [
        'command' => [],
        'other'   => [
            'mautic.leuchtfeuercdn.config' => [
                'class'     => \MauticPlugin\LeuchtfeuerCdnBundle\Integration\Config::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
        ],
        'events'  => [
            'mautic.leuchtfeuercdn.subscriber' => [
                'class'     => \MauticPlugin\LeuchtfeuerCdnBundle\EventListener\OnEmailSendSubscriber::class,
                'arguments' => ['mautic.leuchtfeuercdn.config', '%mautic.site_url%'],
            ],
        ],
        'forms'        => [],
        'models'       => [],
        'fixtures'     => [],
        'integrations' => [
            'mautic.integration.leuchtfeuercdn' => [
                'class' => \MauticPlugin\LeuchtfeuerCdnBundle\Integration\LeuchtfeuerCdnIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            'leuchtfeuercdn.integration.configuration' => [
                'class' => \MauticPlugin\LeuchtfeuerCdnBundle\Integration\Support\ConfigSupport::class,
                'tags'  => [
                    'mautic.config_integration',
                ],
            ],
        ],
    ],
];
