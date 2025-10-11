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
                'label' => 'Police des titres',
                'choices' => [
                    'Montserrat' => 'montserrat',
                    'Darker Grotesque' => 'darker-grotesque',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('bodyFont', ChoiceType::class, [
                'label' => 'Police du corps de texte',
                'choices' => [
                    'Montserrat' => 'montserrat',
                    'Darker Grotesque' => 'darker-grotesque',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('headingSize', ChoiceType::class, [
                'label' => 'Taille des titres',
                'choices' => [
                    'Petit' => 'small',
                    'Moyen' => 'medium',
                    'Grand' => 'large',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('bodySize', ChoiceType::class, [
                'label' => 'Taille du corps de texte',
                'choices' => [
                    'Petit' => 'small',
                    'Moyen' => 'medium',
                    'Grand' => 'large',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('lineHeight', ChoiceType::class, [
                'label' => 'Hauteur de ligne',
                'choices' => [
                    'Serré' => 'tight',
                    'Normal' => 'normal',
                    'Relâché' => 'relaxed',
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
