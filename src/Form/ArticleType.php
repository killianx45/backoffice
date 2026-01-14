<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Tag;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'article.label.title',
                'attr' => [
                    'class' => 'input input-bordered w-full',
                    'placeholder' => 'article.placeholder.title',
                ],
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => false,
                'label' => 'article.label.tags',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'article.label.description',
                'required' => false,
                'attr' => [
                    'class' => 'textarea textarea-bordered w-full',
                    'rows' => 10,
                ],
            ])
            ->add('featuredImage', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'hidden',
                ],
            ])
            ->add('featuredImageFile', FileType::class, [
                'label' => 'article.label.image',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'file-input file-input-bordered w-full',
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'article.error.image_invalid',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'translation_domain' => 'forms',
        ]);
    }
}
