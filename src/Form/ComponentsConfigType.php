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
                'label' => 'components.label.card_style',
                'choices' => [
                    'components.choice.card_style.bordered' => 'bordered',
                    'components.choice.card_style.elevated' => 'elevated',
                    'components.choice.card_style.flat' => 'flat',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('buttonStyle', ChoiceType::class, [
                'label' => 'components.label.button_style',
                'choices' => [
                    'components.choice.button_style.solid' => 'solid',
                    'components.choice.button_style.outline' => 'outline',
                    'components.choice.button_style.ghost' => 'ghost',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('borderRadius', ChoiceType::class, [
                'label' => 'components.label.border_radius',
                'choices' => [
                    'components.choice.border_radius.none' => 'none',
                    'components.choice.border_radius.small' => 'small',
                    'components.choice.border_radius.medium' => 'medium',
                    'components.choice.border_radius.large' => 'large',
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
