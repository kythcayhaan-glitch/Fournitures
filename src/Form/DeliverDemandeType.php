<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\DemandeMateriel;
use App\Entity\LigneDemande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeliverLigneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantiteServie', IntegerType::class, [
                'label' => 'Qté servie',
                'attr'  => ['min' => 0, 'class' => 'form-control form-control-sm'],
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

class DeliverDemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lignes', CollectionType::class, [
                'entry_type'    => DeliverLigneType::class,
                'allow_add'     => false,
                'allow_delete'  => false,
                'by_reference'  => false,
                'label'         => false,
                'entry_options' => ['label' => false],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeMateriel::class,
        ]);
    }
}
