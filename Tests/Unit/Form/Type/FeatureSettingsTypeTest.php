<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\Tests\Form\Unit\Type;

use Mautic\CoreBundle\Form\Type\MultiselectType;
use Mautic\CoreBundle\Form\Type\SortableListType;
use MauticPlugin\LeuchtfeuerCdnBundle\Form\Type\FeatureSettingsType;
use MauticPlugin\LeuchtfeuerCdnBundle\Form\Type\SenderCdnType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class FeatureSettingsTypeTest extends TestCase
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

    public function testBuildForm(): void
    {
        $formType = new FeatureSettingsType();
        $builder  = $this->createMock(FormBuilderInterface::class);
        // If map contains a constraint, then the strict comparison in PHPUnit will not match.
        // Therefore, the `add` method is called as non-chained in the tested class.
        $builder->expects(self::exactly(3))
            ->method('add')
            ->willReturnMap([
                ['cdn', UrlType::class, [
                    'label' => 'plugin.cdn.settings.form.cdn',
                    'attr'  => [
                        'tooltip' => 'plugin.cdn.settings.form.cdn_help',
                        'class'   => 'form-control',
                    ],
                    'label_attr'  => ['class' => 'control-label'],
                    'constraints' => [new NotBlank(['message' => 'plugin.cdn.settings.form.cdn.required'])],
                ]],
                ['cdn_replace', SortableListType::class, [
                    'required'        => false,
                    'label'           => 'plugin.cdn.settings.form.replace',
                    'option_required' => false,
                    'with_labels'     => false, // Needed to invoke a custom form type.
                    'entry_type'      => SenderCdnType::class,
                    'key_value_pairs' => true,
                    'attr'            => ['tooltip' => 'plugin.cdn.settings.form.replace_help'],
                    'help'            => 'plugin.cdn.settings.form.replace_help_extended',
                    'help_html'       => true,
                ]],
                ['extensions', MultiselectType::class, [
                    'label'    => 'plugin.cdn.settings.form.extensions',
                    'multiple' => true,
                    'choices'  => array_combine(self::EXTENSIONS, self::EXTENSIONS),
                ]],
            ]);

        $formType->buildForm($builder, []);
    }
}
