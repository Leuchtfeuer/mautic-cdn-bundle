<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\MultiselectType;
use MauticPlugin\LeuchtfeuerCdnBundle\Form\Type\FeatureSettingsType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

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
    ];

    public function testBuildForm(): void
    {
        $formType = new FeatureSettingsType();
        $builder  = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('add')
            ->willReturnMap([
                ['cdn', UrlType::class, [
                    'label' => 'plugin.cdn.settings.form.cdn',
                ], $builder],
                ['extensions', MultiselectType::class, [
                    'label'    => 'plugin.cdn.settings.form.extensions',
                    'multiple' => true,
                    'choices'  => array_combine(self::EXTENSIONS, self::EXTENSIONS),
                ], $builder],
            ]);

        $formType->buildForm($builder, []);
    }
}
