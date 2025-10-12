<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColorsConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('primary', ColorType::class, [
                'label' => 'colors.label.primary',
                'html5' => true,
                'attr' => [
                    'class' => 'w-full h-12 rounded cursor-pointer',
                    'data-theme-editor-target' => 'colorInput',
                    'data-action' => 'input->theme-editor#updatePreview',
                ],
            ])
            ->add('secondary', ColorType::class, [
                'label' => 'colors.label.secondary',
                'html5' => true,
                'attr' => [
                    'class' => 'w-full h-12 rounded cursor-pointer',
                    'data-theme-editor-target' => 'colorInput',
                    'data-action' => 'input->theme-editor#updatePreview',
                ],
            ])
            ->add('accent', ColorType::class, [
                'label' => 'colors.label.accent',
                'html5' => true,
                'attr' => [
                    'class' => 'w-full h-12 rounded cursor-pointer',
                    'data-theme-editor-target' => 'colorInput',
                    'data-action' => 'input->theme-editor#updatePreview',
                ],
            ])
            ->add('base100', ColorType::class, [
                'label' => 'colors.label.background_primary',
                'html5' => true,
                'attr' => [
                    'class' => 'w-full h-12 rounded cursor-pointer',
                    'data-theme-editor-target' => 'colorInput',
                    'data-action' => 'input->theme-editor#updatePreview',
                ],
            ])
            ->add('base200', ColorType::class, [
                'label' => 'colors.label.background_secondary',
                'html5' => true,
                'attr' => [
                    'class' => 'w-full h-12 rounded cursor-pointer',
                    'data-theme-editor-target' => 'colorInput',
                    'data-action' => 'input->theme-editor#updatePreview',
                ],
            ])
            ->add('base300', ColorType::class, [
                'label' => 'colors.label.border',
                'html5' => true,
                'attr' => [
                    'class' => 'w-full h-12 rounded cursor-pointer',
                    'data-theme-editor-target' => 'colorInput',
                    'data-action' => 'input->theme-editor#updatePreview',
                ],
            ])
            ->add('titleOverlay', ColorType::class, [
                'label' => 'colors.label.title_overlay_bg',
                'html5' => true,
                'required' => false,
                'attr' => [
                    'class' => 'w-full h-12 rounded cursor-pointer',
                    'data-theme-editor-target' => 'colorInput',
                    'data-action' => 'input->theme-editor#updatePreview',
                ],
            ])
            ->add('chartPalette', CollectionType::class, [
                'label' => 'colors.label.chart_colors',
                'entry_type' => ColorType::class,
                'entry_options' => [
                    'label' => false,
                    'html5' => true,
                    'attr' => [
                        'class' => 'w-full h-12 rounded cursor-pointer',
                        'data-theme-editor-target' => 'colorInput',
                        'data-action' => 'input->theme-editor#updatePreview',
                    ],
                ],
                'allow_add' => false,
                'allow_delete' => false,
                'prototype' => false,
                'attr' => ['class' => 'flex gap-2'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'forms',
        ]);
    }
}
