<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TypographyConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('headingFont', ChoiceType::class, [
                'label' => 'typography.label.title_font',
                'choices' => [
                    'Montserrat' => 'montserrat',
                    'Darker Grotesque' => 'darker-grotesque',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('bodyFont', ChoiceType::class, [
                'label' => 'typography.label.body_font',
                'choices' => [
                    'Montserrat' => 'montserrat',
                    'Darker Grotesque' => 'darker-grotesque',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('headingSize', ChoiceType::class, [
                'label' => 'typography.label.title_size',
                'choices' => [
                    'typography.choice.size.small' => 'small',
                    'typography.choice.size.medium' => 'medium',
                    'typography.choice.size.large' => 'large',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('bodySize', ChoiceType::class, [
                'label' => 'typography.label.body_size',
                'choices' => [
                    'typography.choice.size.small' => 'small',
                    'typography.choice.size.medium' => 'medium',
                    'typography.choice.size.large' => 'large',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('lineHeight', ChoiceType::class, [
                'label' => 'typography.label.line_height',
                'choices' => [
                    'typography.choice.line_height.tight' => 'tight',
                    'typography.choice.line_height.normal' => 'normal',
                    'typography.choice.line_height.relaxed' => 'relaxed',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
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
