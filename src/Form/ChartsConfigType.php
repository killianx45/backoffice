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
                'label' => 'charts.label.legend_position',
                'choices' => [
                    'charts.choice.legend_position.top' => 'top',
                    'charts.choice.legend_position.bottom' => 'bottom',
                    'charts.choice.legend_position.left' => 'left',
                    'charts.choice.legend_position.right' => 'right',
                    'charts.choice.legend_position.none' => 'none',
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
