<?php

namespace App\Form;

use App\Entity\MerchantConfiguration;
use App\Entity\Workshop;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkshopType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l’atelier',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '---'
                ]
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Description de l’atelier'
                ]
            ])

            ->add('dailyAmount', MoneyType::class, [
                'label' => 'Montant journalier',
                'currency' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00'
                ]
            ])
              ->add('currency', ChoiceType::class, [
               'label' => 'Devise',
                'placeholder' => '-- Sélectionner --',
                'choices' => [
                    'USD' => 'USD',
                    'CDF' => 'CDF',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('configuration', EntityType::class, [
                'class' => MerchantConfiguration::class,
                'choice_label' => 'shortcode',
                'label' => 'Nom de Marchand',
                'placeholder' => '-- Sélectionner --',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])

            ->add('isEnded', ChoiceType::class, [
                'label' => 'Statut',
                'placeholder' => '-- Sélectionner le statut --',
                'choices' => [
                    'En cours' => false,
                    'Terminé' => true,
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Workshop::class,
        ]);
    }
}
