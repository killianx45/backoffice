<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('layout', LayoutConfigType::class, [
                'label' => false,
            ])
            ->add('typography', TypographyConfigType::class, [
                'label' => false,
            ])
            ->add('colors', ColorsConfigType::class, [
                'label' => false,
            ])
            ->add('charts', ChartsConfigType::class, [
                'label' => false,
            ])
            ->add('components', ComponentsConfigType::class, [
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'forms',
            'attr' => [
                'data-controller' => 'theme-editor',
                'data-action' => 'input->theme-editor#updatePreview change->theme-editor#updatePreview',
            ],
        ]);
    }
}
