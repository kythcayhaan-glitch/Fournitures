<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\DemandeMateriel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motif', TextareaType::class, [
                'label'      => 'Motif de la demande',
                'required'   => false,
                'empty_data' => '',
                'attr'       => [
                    'rows'        => 4,
                    'class'       => 'form-control',
                    'placeholder' => 'Décrivez la raison de votre demande (optionnel)...',
                ],
            ])
            ->add('lignes', CollectionType::class, [
                'entry_type'    => LigneDemandeType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
                'label'         => 'Articles demandés',
                'entry_options' => ['label' => false],
                'attr'          => ['class' => 'lignes-collection'],
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
