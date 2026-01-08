<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\Tests\Form\Unit\Type;

use MauticPlugin\LeuchtfeuerCdnBundle\Form\Type\SenderCdnType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SenderCdnTypeTest extends TestCase
{
    public function testBuildForm(): void
    {
        $formType = new SenderCdnType();
        $builder  = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('add')
            ->willReturnMap([
                ['label', TextType::class, [
                    'label'          => 'plugin.cdn.settings.form.replace_field.sender',
                    'error_bubbling' => true,
                    'attr'           => ['class' => 'form-control'],
                    'constraints'    => [new NotBlank(['message' => 'plugin.cdn.settings.form.replace.sender.required'])],
                ]],
                ['value', UrlType::class, [
                    'label'          => 'plugin.cdn.settings.form.replace_field.cdn',
                    'error_bubbling' => true,
                    'attr'           => ['class' => 'form-control'],
                ]],
            ]);

        $formType->buildForm($builder, []);
    }
}
