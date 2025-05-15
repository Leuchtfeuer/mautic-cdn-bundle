<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\MultiselectType;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

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
        'css',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('cdn', UrlType::class, [
            'label' => 'plugin.cdn.settings.form.cdn',
            'attr'  => [
                'tooltip' => 'plugin.cdn.settings.form.cdn_help',
                'class'   => 'form-control',
            ],
            'label_attr'  => ['class' => 'control-label'],
            'constraints' => [new NotBlank(['message' => 'plugin.cdn.settings.form.cdn.required'])],
        ]);

        $builder->add(
            'cdn_replace',
            SortableListType::class,
            [
                'required'         => false,
                'label'            => 'plugin.cdn.settings.form.replace',
                'option_required'  => false,
                'with_labels'      => false, // Needed to invoke a custom form type.
                'entry_type'       => SenderCdnType::class,
                'key_value_pairs'  => true,
                'attr'             => ['tooltip' => 'plugin.cdn.settings.form.replace_help'],
                'help'             => 'plugin.cdn.settings.form.replace_help_extended',
                'help_html'        => true,
                'add_value_button' => 'plugin.cdn.settings.form.replace_button',
            ]
        );

        $builder->add('extensions', MultiselectType::class, [
            'label'    => 'plugin.cdn.settings.form.extensions',
            'multiple' => true,
            'choices'  => array_combine(self::EXTENSIONS, self::EXTENSIONS),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Needed for M4
        $resolver->setDefault('default_theme', version_compare(MAUTIC_VERSION, '5.0', '<') ? 'LeuchtfeuerCdnBundle:FormTheme\Custom' : '@LeuchtfeuerCdn/FormTheme/Custom');
        $resolver->setAllowedTypes('default_theme', 'string');
    }
}
