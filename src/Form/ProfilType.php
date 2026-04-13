<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label'       => 'Prénom',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
                'attr'        => ['class' => 'form-control'],
            ])
            ->add('lastName', TextType::class, [
                'label'       => 'Nom',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
                'attr'        => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'Adresse email',
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
                'attr'        => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
