<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChartsConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('legendPosition', ChoiceType::class, [
                'label' => 'Position de la légende',
                'choices' => [
                    'Haut' => 'top',
                    'Bas' => 'bottom',
                    'Gauche' => 'left',
                    'Droite' => 'right',
                    'Aucune' => 'none',
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
