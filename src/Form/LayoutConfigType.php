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
                'label' => 'Largeur de l\'article',
                'choices' => [
                    'Étroit (600px)' => 'narrow',
                    'Moyen (800px)' => 'medium',
                    'Large (1200px)' => 'wide',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('featuredImageSize', ChoiceType::class, [
                'label' => 'Taille de l\'image mise en avant',
                'choices' => [
                    'Petite' => 'small',
                    'Moyenne' => 'medium',
                    'Grande' => 'large',
                    'Pleine largeur' => 'full',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('contentAlignment', ChoiceType::class, [
                'label' => 'Alignement du contenu',
                'choices' => [
                    'Gauche' => 'left',
                    'Centre' => 'center',
                    'Justifié' => 'justify',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('spacing', ChoiceType::class, [
                'label' => 'Espacement',
                'choices' => [
                    'Compact' => 'compact',
                    'Confortable' => 'comfortable',
                    'Spacieux' => 'spacious',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('titlePosition', ChoiceType::class, [
                'label' => 'Position du titre',
                'choices' => [
                    'Au-dessus de l\'image' => 'above',
                    'Sur l\'image (haut)' => 'overlay-top',
                    'Sur l\'image (centre)' => 'overlay-center',
                    'Sur l\'image (bas)' => 'overlay-bottom',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
