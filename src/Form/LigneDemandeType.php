<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Article;
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
            ->add('article', EntityType::class, [
                'class'        => Article::class,
                'choice_label' => fn(Article $a) => sprintf('[%s] %s (stock: %d %s)', $a->getReference(), $a->getName(), $a->getStockQuantity(), $a->getUnit()),
                'choice_attr'  => fn(Article $a) => ['data-stock' => $a->getStockQuantity(), 'data-name' => $a->getName()],
                'placeholder'  => '-- Sélectionner un article --',
                'query_builder' => function (\App\Repository\ArticleRepository $repo) {
                    return $repo->createQueryBuilder('a')
                        ->orderBy('a.name', 'ASC');
                },
                'label'        => 'Article',
                'attr'         => ['class' => 'form-select select-article'],
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
