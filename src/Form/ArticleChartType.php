<?php

namespace App\Form;

use App\Entity\ArticleChart;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleChartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('csvFile', HiddenType::class, [
                'required' => false,
            ])
            ->add('chartType', ChoiceType::class, [
                'label' => 'Type de graphique',
                'choices' => [
                    'Barres' => 'bar',
                    'Ligne' => 'line',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre du graphique (optionnel)',
                'required' => false,
                'attr' => ['class' => 'input input-bordered w-full', 'placeholder' => 'Ex: Évolution des ventes'],
            ])
            ->add('displayOrder', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'attr' => ['class' => 'input input-bordered w-full', 'min' => 0],
                'data' => 0,
            ])
            ->add('xAxisLabel', TextType::class, [
                'label' => 'Libellé de l\'axe X (optionnel)',
                'required' => false,
                'attr' => ['class' => 'input input-bordered w-full', 'placeholder' => 'Ex: Semaines'],
            ])
            ->add('yAxisLabel', TextType::class, [
                'label' => 'Libellé de l\'axe Y (optionnel)',
                'required' => false,
                'attr' => ['class' => 'input input-bordered w-full', 'placeholder' => 'Ex: Valeur'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArticleChart::class,
        ]);
    }
}
