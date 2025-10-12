<?php

namespace App\Form;

use App\Entity\Theme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'theme.label.name',
                'attr' => ['class' => 'input input-bordered w-full'],
            ])
            ->add('slug', TextType::class, [
                'label' => 'theme.label.slug',
                'attr' => [
                    'class' => 'input input-bordered w-full',
                    'placeholder' => 'theme.placeholder.slug',
                ],
                'help' => 'theme.help.slug',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'theme.label.description',
                'required' => false,
                'attr' => ['class' => 'textarea textarea-bordered w-full', 'rows' => 3],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'theme.label.is_active',
                'required' => false,
                'attr' => ['class' => 'checkbox'],
            ])
            ->add('configuration', ConfigurationType::class, [
                'label' => false,
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Theme::class,
            'translation_domain' => 'forms',
        ]);
    }
}
