<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label'       => 'Prénom',
                'attr'        => ['class' => 'form-control'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            ])
            ->add('lastName', TextType::class, [
                'label'       => 'Nom',
                'attr'        => ['class' => 'form-control'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,
                'required'        => $options['require_password'],
                'first_options'   => [
                    'label' => $options['require_password'] ? 'Mot de passe' : 'Nouveau mot de passe (laisser vide pour ne pas changer)',
                    'attr'  => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                ],
                'second_options'  => [
                    'label' => 'Confirmer le mot de passe',
                    'attr'  => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints'     => $options['require_password']
                    ? [new Assert\NotBlank(), new Assert\Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
                    : [new Assert\Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')],
            ])
            ->add('role', ChoiceType::class, [
                'label'   => 'Rôle',
                'mapped'  => false,
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Manager'     => 'ROLE_MANAGER',
                    'Admin'       => 'ROLE_ADMIN',
                ],
                'attr'    => ['class' => 'form-select'],
                'data'    => 'ROLE_USER',
            ])
        ;

        if ($options['show_active']) {
            $builder->add('isActive', CheckboxType::class, [
                'label'    => 'Compte actif',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'       => User::class,
            'require_password' => true,
            'show_active'      => false,
        ]);
    }
}
