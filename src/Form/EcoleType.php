<?php

namespace App\Form;

use App\Entity\Ecole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EcoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('nom', TextType::class, [
                'label' => 'Nom de l’école',
                'row_attr' => [
                    'class' => 'mb-0'
                ],

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. Institut Technique Médical',
                ],
            ])

            ->add('adresse', TextType::class, [
                'label' => 'Adresse',

                'row_attr' => [
                    'class' => 'mb-0'
                ],

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Adresse de l’école',
                ],
            ])

            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,

                'row_attr' => [
                    'class' => 'mb-0'
                ],

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. +243 81 000 00 00',
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'required' => false,

                'row_attr' => [
                    'class' => 'mb-0'
                ],

                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. contact@ecole.cd',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ecole::class,
        ]);
    }
}