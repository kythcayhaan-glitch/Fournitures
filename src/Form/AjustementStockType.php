<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class AjustementStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nouvelleQuantite', IntegerType::class, [
                'label' => 'Nouvelle quantité en stock',
                'attr'  => ['min' => 0, 'class' => 'form-control'],
                'constraints' => [new NotBlank(), new PositiveOrZero()],
            ])
            ->add('motif', TextType::class, [
                'label'    => 'Motif de l\'ajustement',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'Ex: Inventaire physique du 01/01/2025'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
