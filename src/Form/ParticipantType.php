<?php

namespace App\Form;

use App\Entity\Biometric;
use App\Entity\Participant;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '---'
                ]
            ])

            ->add('middlename', TextType::class, [
                'label' => 'Postnom',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '---'
                ]
            ])

            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '---'
                ]
            ])

            ->add('gender', ChoiceType::class, [
                'label' => 'Sexe',
                'placeholder' => '-- Sélectionner --',
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin' => 'F',
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])

            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '243XXXXXXXXX'
                ]
            ])

            ->add('phoneMobileMoney', TelType::class, [
                'label' => 'Téléphone Mobile Money',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '243XXXXXXXXX'
                ]
            ]) 

            ->add('organization', TextType::class, [
                'label' => 'Organisation',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entreprise ou Institution'
                ]
            ])

            ->add('grade', TextType::class, [
                'label' => 'Fonction / Grade',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Fonction'
                ]
            ])

            // ->add('biometric', EntityType::class, [
            //     'class' => Biometric::class,
            //     'choice_label' => 'id',
            //     'label' => 'Empreinte biométrique',
            //     'placeholder' => '-- Sélectionner --',
            //     'attr' => [
            //         'class' => 'form-select'
            //     ]
            // ])
        
             ->add('enabled', ChoiceType::class, [
                'label' => 'Statut',
                'placeholder' => '-- Sélectionner le statut --',
                'choices' => [
                    'Actif' => true,
                    'Inactif' => false,
                    
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);

        // Les champs suivants sont supprimés du formulaire :
        // code
        // deleted
        // createdAt
        // updatedAt
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}