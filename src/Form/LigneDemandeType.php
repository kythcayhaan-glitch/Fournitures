<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Fourniture;
use App\Entity\LigneDemande;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LigneDemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fourniture', EntityType::class, [
                'class'        => Fourniture::class,
                'choice_label' => fn(Fourniture $f) => sprintf('[%s] %s (stock: %d %s)', $f->getReference(), $f->getName(), $f->getStockQuantity(), $f->getUnit()),
                'choice_attr'  => fn(Fourniture $f) => ['data-stock' => $f->getStockQuantity(), 'data-name' => $f->getName()],
                'placeholder'  => '-- Sélectionner une fourniture --',
                'query_builder' => function (\App\Repository\FournitureRepository $repo) {
                    return $repo->createQueryBuilder('f')
                        ->andWhere('f.isActive = true')
                        ->orderBy('f.name', 'ASC');
                },
                'label'        => 'Fourniture',
                'attr'         => ['class' => 'form-select select-fourniture'],
            ])
            ->add('quantiteDemandee', IntegerType::class, [
                'label'      => 'Quantité',
                'empty_data' => 1,
                'attr'       => [
                    'min'   => 1,
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LigneDemande::class,
        ]);
    }
}
