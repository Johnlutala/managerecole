<?php

namespace App\Form;

use App\Entity\AnneeScolaire;
use App\Entity\Ecole;
use App\Entity\Professeur;
use App\Entity\Section;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProfesseurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom'
            ])

            ->add('postnom', TextType::class, [
                'label' => 'Postnom',
                'required' => false
            ])

            ->add('prenom', TextType::class, [
                'label' => 'Prénom'
            ])

            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin' => 'F'
                ],
                'placeholder' => 'Sélectionner'
            ])

            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false
            ])

            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false
            ])

            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'required' => false
            ])

            ->add('adresse', TextType::class, [
                'label' => 'Adresse'
            ])

            ->add('specialite', TextType::class, [
                'label' => 'Spécialité',
                'required' => false
            ])

            ->add('dateEmbauche', DateType::class, [
                'label' => "Date d'embauche",
                'widget' => 'single_text'
            ])

            ->add('grade', TextType::class, [
                'label' => 'grade',
                'required' => false
            ])

            ->add('ecole', EntityType::class, [
                'class' => Ecole::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une école',
                'label' => 'École',
                'required' => true,
            ])

            ->add('anneeScolaire', EntityType::class, [
                'class' => AnneeScolaire::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner une année scolaire',
                'label' => 'Année scolaire',
                'required' => true,
            ])

            ->add('section', EntityType::class, [
                'class' => Section::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une section',
                'label' => 'Section',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Professeur::class,
        ]);
    }
}