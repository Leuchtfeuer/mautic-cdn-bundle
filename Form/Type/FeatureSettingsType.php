<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCdnBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\MultiselectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class FeatureSettingsType extends AbstractType
{
    private const EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'svg',
        'webp',
        'gif',
        'tiff',
        'pdf',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('cdn', UrlType::class, [
            'label' => 'plugin.cdn.settings.form.cdn',
        ])->add('extensions', MultiselectType::class, [
            'label'    => 'plugin.cdn.settings.form.extensions',
            'multiple' => true,
            'choices'  => array_combine(self::EXTENSIONS, self::EXTENSIONS),
        ]);
    }
}
