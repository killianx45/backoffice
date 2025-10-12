<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'label' => 'registration.label.lastname',
                'attr' => [
                    'placeholder' => 'registration.placeholder.lastname',
                ],
            ])
            ->add('prenom', null, [
                'label' => 'registration.label.firstname',
                'attr' => [
                    'placeholder' => 'registration.placeholder.firstname',
                ],
            ])
            ->add('email', null, [
                'label' => 'registration.label.email',
                'attr' => [
                    'placeholder' => 'registration.placeholder.email',
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'registration.label.agree_terms',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'registration.error.agree_terms',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'registration.label.password',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'registration.placeholder.password',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'registration.error.password_required',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'registration.error.password_min_length',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'forms',
        ]);
    }
}
