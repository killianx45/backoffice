<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComponentsConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cardStyle', ChoiceType::class, [
                'label' => 'Style des cartes',
                'choices' => [
                    'Bordure' => 'bordered',
                    'Élevé' => 'elevated',
                    'Plat' => 'flat',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('buttonStyle', ChoiceType::class, [
                'label' => 'Style des boutons',
                'choices' => [
                    'Solide' => 'solid',
                    'Contour' => 'outline',
                    'Fantôme' => 'ghost',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('borderRadius', ChoiceType::class, [
                'label' => 'Arrondi des bordures',
                'choices' => [
                    'Aucun' => 'none',
                    'Petit' => 'small',
                    'Moyen' => 'medium',
                    'Grand' => 'large',
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
