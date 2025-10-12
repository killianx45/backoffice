<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('avatar', FileType::class, [
                'label' => 'profile.label.avatar',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'profile.error.avatar_invalid',
                    ])
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'profile.label.lastname',
                'disabled' => true,
                'attr' => [
                    'readonly' => true,
                    'class' => 'bg-base-200 cursor-not-allowed',
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'profile.label.firstname',
                'disabled' => true,
                'attr' => [
                    'readonly' => true,
                    'class' => 'bg-base-200 cursor-not-allowed',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'profile.label.email',
                'disabled' => true,
                'attr' => [
                    'readonly' => true,
                    'class' => 'bg-base-200 cursor-not-allowed',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'profile.label.new_password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'placeholder' => 'profile.placeholder.password',
                    ],
                ],
                'second_options' => [
                    'label' => 'profile.label.confirm_password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'placeholder' => 'profile.placeholder.password',
                    ],
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'profile.error.password_min_length',
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
