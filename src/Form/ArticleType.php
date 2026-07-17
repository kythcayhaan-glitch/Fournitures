<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Article;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Ex: Ramette papier A4'],
                'constraints' => [new NotBlank()],
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Ex: PAP-A4-80G'],
                'constraints' => [new NotBlank()],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('unitPrice', NumberType::class, [
                'label'       => 'Prix unitaire (€)',
                'scale'       => 2,
                'attr'        => ['class' => 'form-control', 'min' => 0.01, 'step' => 0.01],
                'constraints' => [new NotBlank(), new Positive()],
            ])
            ->add('unit', ChoiceType::class, [
                'label'   => 'Unité',
                'choices' => [
                    'Unité'    => 'unité',
                    'Boîte'    => 'boîte',
                    'Ramette'  => 'ramette',
                    'Paquet'   => 'paquet',
                    'Carton'   => 'carton',
                    'Rouleau'  => 'rouleau',
                    'Flacon'   => 'flacon',
                    'Litre'    => 'litre',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('stockMinimum', IntegerType::class, [
                'label' => 'Stock minimum',
                'attr'  => ['class' => 'form-control', 'min' => 0],
                'constraints' => [new PositiveOrZero()],
            ])
        ;

        if ($options['is_creation']) {
            $builder->add('stockQuantity', IntegerType::class, [
                'label'       => 'Stock réel',
                'mapped'      => false,
                'data'        => 0,
                'attr'        => ['class' => 'form-control', 'min' => 0],
                'constraints' => [new PositiveOrZero()],
            ]);
        }

        $builder
            ->add('category', EntityType::class, [
                'class'        => Category::class,
                'choice_label' => 'name',
                'placeholder'  => '-- Choisir une catégorie --',
                'label'        => 'Catégorie',
                'attr'         => ['class' => 'form-select'],
                'constraints'  => [new NotBlank()],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'  => Article::class,
            'is_creation' => false,
        ]);
    }
}
