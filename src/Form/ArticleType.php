<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'class' => 'input input-bordered w-full',
                    'placeholder' => 'Entrez le titre de l\'article'
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
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
                'label' => 'Image principale',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'file-input file-input-bordered w-full',
                    'accept' => 'image/*'
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
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF, WebP)',
                    ])
                ],
            ])
            ->add('csvFileFile', FileType::class, [
                'label' => 'Fichier CSV (pour graphiques)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'file-input file-input-bordered w-full',
                    'accept' => '.csv,text/csv',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'text/csv',
                            'text/plain',
                            'application/csv',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier CSV valide',
                    ])
                ],
            ])
            ->add('chartType', ChoiceType::class, [
                'label' => 'Type de graphique',
                'required' => false,
                'placeholder' => 'Sélectionnez un type de graphique',
                'choices' => [
                    'Barres' => 'bar',
                    'Ligne' => 'line',
                    'Camembert' => 'pie',
                    'Aires' => 'area',
                    'Nuage de points' => 'scatter',
                    'Anneau' => 'doughnut',
                    'Radar' => 'radar',
                ],
                'attr' => [
                    'class' => 'select select-bordered w-full',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
