<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LayoutConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('articleWidth', ChoiceType::class, [
                'label' => 'layout.label.article_width',
                'choices' => [
                    'layout.choice.article_width.narrow' => 'narrow',
                    'layout.choice.article_width.medium' => 'medium',
                    'layout.choice.article_width.wide' => 'wide',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('featuredImageSize', ChoiceType::class, [
                'label' => 'layout.label.featured_image_size',
                'choices' => [
                    'layout.choice.image_size.small' => 'small',
                    'layout.choice.image_size.medium' => 'medium',
                    'layout.choice.image_size.large' => 'large',
                    'layout.choice.image_size.full' => 'full',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('contentAlignment', ChoiceType::class, [
                'label' => 'layout.label.content_alignment',
                'choices' => [
                    'layout.choice.alignment.left' => 'left',
                    'layout.choice.alignment.center' => 'center',
                    'layout.choice.alignment.justify' => 'justify',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('spacing', ChoiceType::class, [
                'label' => 'layout.label.spacing',
                'choices' => [
                    'layout.choice.spacing.compact' => 'compact',
                    'layout.choice.spacing.comfortable' => 'comfortable',
                    'layout.choice.spacing.spacious' => 'spacious',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('titlePosition', ChoiceType::class, [
                'label' => 'layout.label.title_position',
                'choices' => [
                    'layout.choice.title_position.above' => 'above',
                    'layout.choice.title_position.overlay_top' => 'overlay-top',
                    'layout.choice.title_position.overlay_center' => 'overlay-center',
                    'layout.choice.title_position.overlay_bottom' => 'overlay-bottom',
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
