<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'row_attr' => [
                    'class' => 'mb-0'
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '---'
                ]
            ])

            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'row_attr' => [
                    'class' => 'mb-0'
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '---'
                ]
            ])

            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'row_attr' => [
                    'class' => 'mb-0'
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@email.com'
                ]
            ])

            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle(s)',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'row_attr' => [
                    'class' => 'mb-0'
                ],
            ])

            ->add('enabled', ChoiceType::class, [
                'label' => 'Statut',
                'placeholder' => '-- Sélectionner le statut --',
                'choices' => [
                    'Actif' => true,
                    'Inactif' => false,
                ],
                'row_attr' => [
                    'class' => 'mb-0'
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}