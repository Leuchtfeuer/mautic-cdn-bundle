<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCdnBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints\NotBlank;

class SenderCdnType extends AbstractType
{
    /**
     * @param array<mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'label',
            TextType::class,
            [
                'label'          => 'plugin.cdn.settings.form.replace_field.sender',
                'error_bubbling' => true,
                'attr'           => ['class' => 'form-control'],
                'constraints'    => [new NotBlank(['message' => 'plugin.cdn.settings.form.replace.sender.required'])],
            ]
        );

        $builder->add(
            'value',
            UrlType::class,
            [
                'label'          => 'plugin.cdn.settings.form.replace_field.cdn',
                'error_bubbling' => true,
                'attr'           => ['class' => 'form-control'],
            ]
        );
    }

    /**
     * @param array<mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['preaddonAttr']  = $options['attr']['preaddon_attr'] ?? [];
        $view->vars['postaddonAttr'] = $options['attr']['postaddon_attr'] ?? [];
        $view->vars['preaddon']      = $options['attr']['preaddon'] ?? [];
        $view->vars['postaddon']     = $options['attr']['postaddon'] ?? [];
    }
}
