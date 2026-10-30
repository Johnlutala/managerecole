<?php

namespace App\Form;

use App\Entity\AnneeScolaire;
use App\Entity\Classe;
use App\Entity\ConfigurationBulletin;
use App\Entity\Ecole;
use App\Entity\ModeleBulletin;
use App\Entity\Option;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationBulletinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la période',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. Premier trimestre',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut de la période',
                'choices' => [
                    'Brouillon' => 'brouillon',
                    'Autorisé à saisir' => 'autorise',
                    'Fermé' => 'ferme',
                ],
                'placeholder' => 'Sélectionnez un statut',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('modeleBulletin', EntityType::class, [
                'class' => ModeleBulletin::class,
                'choice_label' => 'nom',
                'label' => 'Modèle de bulletin',
                'placeholder' => 'Sélectionnez un modèle',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('ecole', EntityType::class, [
                'class' => Ecole::class,
                'choice_label' => 'nom',
                'label' => 'École',
                'placeholder' => 'Sélectionnez une école',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('anneeScolaire', EntityType::class, [
                'class' => AnneeScolaire::class,
                'choice_label' => 'libelle',
                'label' => 'Année scolaire',
                'placeholder' => 'Sélectionnez une année scolaire',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('classe', EntityType::class, [
                'class' => Classe::class,
                'choice_label' => 'nom',
                'label' => 'Classe',
                'placeholder' => 'Sélectionnez une classe',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('options', EntityType::class, [
                'class' => Option::class,
                'choice_label' => 'nom',
                'label' => 'Option',
                'required' => false,
                'placeholder' => 'Aucune option',
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfigurationBulletin::class,
        ]);
    }
}
